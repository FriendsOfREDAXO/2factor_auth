<?php

use FriendsOfREDAXO\TwoFactorAuth\method_email;
use FriendsOfREDAXO\TwoFactorAuth\method_totp;
use FriendsOfREDAXO\TwoFactorAuth\one_time_password;

/** @var rex_addon $this */

$error = false;
$info_messages = [];
$error_messages = [];
$csrfToken = rex_csrf_token::factory('2factor_auth_verify');
$otp = rex_post('rex_login_otp', 'string', null);
$OTPInstance = one_time_password::getInstance();
$Method = $OTPInstance->getMethod();

if (!isset($otp)) {
    try {
        one_time_password::getInstance()->challenge();
    } catch (Exception $e) {
        $error = true;
        $error_messages[] = $e->getMessage();
    }
}

switch (get_class($Method)) {
    case 'FriendsOfREDAXO\TwoFactorAuth\method_email':
        if (!isset($otp)) {
            $info_messages[] = rex_i18n::msg('2factor_auth_2fa_info_email_sent');
        }
        $info_messages[] = rex_i18n::msg('2factor_auth_2fa_info_email_enter_code');
        $blockTime = method_email::getPeriod();
        $loginTriesAllowed = method_email::getloginTries();
        break;
    default:
    case 'FriendsOfREDAXO\TwoFactorAuth\method_totp':
        $info_messages[] = rex_i18n::msg('2factor_auth_2fa_info_topt_enter_code');
        $blockTime = method_totp::getPeriod();
        $loginTriesAllowed = method_totp::getloginTries();
        break;
}

/** @var rex_user $user */
$user = rex::getUser();
$loginTries = (int) $user->getValue('one_time_password_tries');
$loginLastTry = (int) $user->getValue('one_time_password_lasttry');

$SQLUser = rex_sql::factory();
$SQLUser->setTable(rex::getTable('user'));
$SQLUser->setWhere('id = :id', ['id' => $user->getId()]);

if (isset($otp) && !$csrfToken->isValid()) {
    $error_messages[] = rex_i18n::msg('csrf_token_invalid');
} elseif ($loginTries >= $loginTriesAllowed && ($loginLastTry > time() - $blockTime)) {
    $countdownTime = $loginLastTry - time() + $blockTime;
    $error_messages[] = rex_i18n::rawMsg('one_time_password_too_many_tries', $countdownTime, $loginTriesAllowed, $blockTime);

    $script = '
    <script nonce="' . rex_response::getNonce() . '">

    let countdown = ' . $countdownTime . ';
    let countdownElement = document.getElementById("otp_countdown");

    let interval = setInterval(() => {
       countdown--;
       countdownElement.innerHTML = countdown;
       if (countdown <= 0) {
           clearInterval(interval);
       }
    }, 1000);

</script>
    ';
} elseif (isset($otp) && '' == $otp) {
    // $error_messages[] = rex_i18n::msg('2fa_otp_empty');
} elseif (isset($otp)) {


    if ($OTPInstance->verify($otp)) {
        $SQLUser->setValue('one_time_password_tries', 0);
        $SQLUser->setValue('one_time_password_lasttry', time());
        $SQLUser->update();

        $message = rex_view::success('Passt');
        // symbolischer parameter, der nirgends ausgewertet werden sollte/darf.
        rex_response::sendRedirect('?ok');
    } else {
        $SQLUser->setValue('one_time_password_tries', $loginTries + 1);
        $SQLUser->setValue('one_time_password_lasttry', time());
        $SQLUser->update();

        $error_messages[] = $this->i18n('2fa_otp_wrong');
    }
}

$fragment = new rex_fragment();
$fragment->setVar('csrfToken', $csrfToken, false);
$fragment->setVar('info_messages', implode('<br />', $info_messages), false);
$fragment->setVar('error_messages', implode('<br />', $error_messages), false);
echo $fragment->parse('2fa.login.php');
echo $script ?? '';

?>


