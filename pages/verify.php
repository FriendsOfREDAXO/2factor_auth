<?php

echo rex_view::title(rex_i18n::msg('2factor_auth_login'), '');

$csrfToken = rex_csrf_token::factory('2factor_auth_verify');
$otp = rex_post('rex_login_otp', 'string');

if ($otp && !$csrfToken->isValid()) {
    echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    $otp = '';
}

$message = '';
if ($otp) {
    if (rex_one_time_password::getInstance()->verify($otp)) {
        $message = rex_view::success('Passt');

        rex_response::sendRedirect('?ok');
    } else {
        $message = rex_view::warning('Falsches one-time-password, bitte erneut versuchen');
    }
}

echo $message;
?>
<form method="post" autocomplete="off">
    <p>Login mittels einmal passwort bestätigen</p>
    <?php echo $csrfToken->getHiddenField(); ?>
    <input type="hidden" name="page" value="2factor_auth_verify"/>
    <input type="text" name="rex_login_otp" autocomplete="one-time-code" />
    <input type="submit" value="Anmelden" />
</form>

<br />
<p>
    <a class="rex-logout" href="<?php echo rex_url::backendController(['rex_logout' => 1] + rex_csrf_token::factory('backend_logout')->getUrlParams()); ?>"><i class="rex-icon rex-icon-sign-out"></i><?php echo rex_i18n::msg('logout'); ?></a>
</p>
