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
        $message = rex_view::success(rex_i18n::msg('2fa_verified'));
        
        rex_response::sendRedirect('?ok');
    } else {
        $message = rex_view::error(rex_i18n::msg('2factor_auth_2fa_wrong_opt'));
    }
}


echo $message;

$buttons = '<a class="btn btn-primary rex-logout" href="' . rex_url::backendController(['rex_logout' => 1] + rex_csrf_token::factory('backend_logout')->getUrlParams()) .'"><i class="rex-icon rex-icon-sign-out"></i> '. rex_i18n::msg('logout') .'</a>';

$fragment->setVar('csrfToken', $csrfToken, false);
$content = $fragment->parse('2fa.setup.verification.php');

$fragment = new rex_fragment();
$fragment->setVar('heading', rex_i18n::msg('2fa_verify_instruction'), false);
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
echo $fragment->parse('core/page/section.php');
