<?php

use rex_2fa\one_time_password;

$error = false;
$message = '';
$csrfToken = rex_csrf_token::factory('2factor_auth_verify');
$otp = rex_post('rex_login_otp', 'string');

try {
    if ('' === $otp) {
        one_time_password::getInstance()->challenge();
    }

    if ('' !== $otp && !$csrfToken->isValid()) {
        $error = true;
        $message = rex_i18n::msg('csrf_token_invalid');
        $otp = '';
    }

    if ('' !== $otp) {
        if (one_time_password::getInstance()->verify($otp)) {
            $message = rex_view::success('Passt');
            // symbolischer parameter, der nirgends ausgewertet werden sollte/darf.
            rex_response::sendRedirect('?ok');
        } else {
            // requires redaxo-core 5.14+
            if (method_exists(rex_backend_login::class, 'increaseLoginTries')) {
                $backendLogin = rex::getProperty('login');
                $backendLogin->increaseLoginTries();
            }

            $error = true;
            $message = $this->i18n('2fa_otp_wrong');
        }
    }
} catch (\rex_2fa\exception $e) {
    $error = true;
    $message = $e->getMessage();
}

$fragment = new rex_fragment();
$fragment->setVar('csrfToken', $csrfToken, false);
$fragment->setVar('error', $error, false);
$fragment->setVar('message', $message, false);
echo $fragment->parse('2fa.login.php');
