<?php

use rex_2fa\one_time_password;

/** @var rex_addon $this */

$error = false;
$info_messages = [];
$error_messages = [];
$csrfToken = rex_csrf_token::factory('2factor_auth_verify');
$otp = rex_post('rex_login_otp', 'string', null);

if (!isset($otp)) {
    one_time_password::getInstance()->challenge();
}

switch (get_class(one_time_password::getInstance()->getMethod())) {
    case 'rex_2fa\method_email':
        if (!isset($otp)) {
            $info_messages[] = rex_i18n::msg('2factor_auth_2fa_info_email_sent');
        }
        $info_messages[] = rex_i18n::msg('2factor_auth_2fa_info_email_enter_code');
        break;
    default:
    case 'rex_2fa\method_totp':
        $info_messages[] = rex_i18n::msg('2factor_auth_2fa_info_topt_enter_code');
        break;
}

if (isset($otp) && !$csrfToken->isValid()) {
    $error_messages[] = rex_i18n::msg('csrf_token_invalid');
    $otp = '';
}

if (isset($otp)) {
    if (one_time_password::getInstance()->verify($otp)) {
        $message = rex_view::success('Passt');
        // symbolischer parameter, der nirgends ausgewertet werden sollte/darf.
        rex_response::sendRedirect('?ok');
    } else {
        // requires redaxo-core 5.14+
        /** @phpstan-ignore-next-line */
        if (method_exists(rex_backend_login::class, 'increaseLoginTries')) {
            $backendLogin = rex::getProperty('login');
            if (null !== $backendLogin && 'rex_backend_login' == get_class($backendLogin)) {
                $backendLogin->increaseLoginTries();
            }
        }

        $error_messages[] = $this->i18n('2fa_otp_wrong');
    }
}

$fragment = new rex_fragment();
$fragment->setVar('csrfToken', $csrfToken, false);
$fragment->setVar('info_messages', implode('<br />', $info_messages), false);
$fragment->setVar('error_messages', implode('<br />', $error_messages), false);
echo $fragment->parse('2fa.login.php');
