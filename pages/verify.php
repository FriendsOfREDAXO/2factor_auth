<?php

use FriendsOfREDAXO\TwoFactorAuth\method_email;
use FriendsOfREDAXO\TwoFactorAuth\one_time_password;

/** @var rex_addon $this */

$info_messages = [];
$error_messages = [];
$csrfToken = rex_csrf_token::factory('2factor_auth_verify');
$otp = rex_post('rex_login_otp', 'string', null);
$OTPInstance = one_time_password::getInstance();
$Method = $OTPInstance->getMethod();

$blockTime = $Method::getPeriod();
$loginTriesAllowed = $Method::getloginTries();
$blockedSeconds = $OTPInstance->getBlockedSeconds();

$challengeSent = false;
if (!isset($otp) && 0 === $blockedSeconds) {
    try {
        $challengeSent = $OTPInstance->challenge();
    } catch (Exception $e) {
        $error_messages[] = $e->getMessage();
    }
}

if ($Method instanceof method_email) {
    if (!isset($otp)) {
        $info_messages[] = $challengeSent
            ? rex_i18n::msg('2factor_auth_2fa_info_email_sent')
            : rex_i18n::msg('2factor_auth_2fa_info_email_still_valid');
    }
    $info_messages[] = rex_i18n::msg('2factor_auth_2fa_info_email_enter_code');
} else {
    $info_messages[] = rex_i18n::msg('2factor_auth_2fa_info_topt_enter_code');
}

if (isset($otp) && !$csrfToken->isValid()) {
    $error_messages[] = rex_i18n::msg('csrf_token_invalid');
} elseif ($blockedSeconds > 0) {
    $error_messages[] = rex_i18n::rawMsg('one_time_password_too_many_tries', $blockedSeconds, $loginTriesAllowed, $blockTime);

    $script = '
    <script nonce="' . rex_response::getNonce() . '">

    let countdown = ' . $blockedSeconds . ';
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
        $OTPInstance->resetFailedAttempts();

        // symbolischer parameter, der nirgends ausgewertet werden sollte/darf.
        rex_response::sendRedirect('?ok');
    } else {
        $OTPInstance->registerFailedAttempt();

        $error_messages[] = $this->i18n('2fa_otp_wrong');
    }
}

$fragment = new rex_fragment();
$fragment->setVar('csrfToken', $csrfToken, false);
$fragment->setVar('info_messages', implode('<br />', $info_messages), false);
$fragment->setVar('error_messages', implode('<br />', $error_messages), false);
echo $fragment->parse('2fa.login.php');
echo $script ?? '';
