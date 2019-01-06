<?php

echo rex_view::title(rex_i18n::msg('2factor_auth_login'), '');

$otp = rex_post('rex_login_otp', 'string');


$message = '';
if ($otp) {
    if (rex_one_time_password::getInstance()->verify($otp)) {
        // alles gut, weiter gehts
        $message = 'Passt';
    } else {
        // falsches otp
        $message = 'Falsches one-time-password, bitte erneut versuchen';
    }
}

var_dump($message);
?>
<form method="post">
    <input type="hidden" name="page" value="2factor_auth/login"/>
    <input type="text" name="rex_login_otp"/>
    <input type="submit"/>
</form>