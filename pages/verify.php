<?php

echo rex_view::title(rex_i18n::msg('2factor_auth_login'), '');

$otp = rex_post('rex_login_otp', 'string');


$message = '';
if ($otp) {
    if (rex_one_time_password::getInstance()->verify($otp)) {
        rex_view::success('Passt');
    } else {
        rex_view::warning('Falsches one-time-password, bitte erneut versuchen');
    }
}

echo $message;
?>
<form method="post">
    <input type="hidden" name="page" value="2factor_auth_verify"/>
    <input type="text" name="rex_login_otp"/>
    <input type="submit"/>
</form>