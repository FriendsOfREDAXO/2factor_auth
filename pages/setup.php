<?php

echo rex_view::title($this->i18n('page_setup'), '');

$csrfToken = rex_csrf_token::factory('2factor_auth_setup');
$func = rex_request('func', 'string');

$otp = rex_one_time_password::getInstance();

if ($func && !$csrfToken->isValid()) {
    echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    $func = '';
}

if ($func === 'disable') {
    $config = rex_one_time_password_config::loadFromDb();
    $config->disable();

    $func = '';
}

if ($otp->enabled()) {
    echo rex_view::info($this->i18n('2fa_active'));

    $buttons = '<a class="btn btn-delete" href="' . rex_url::currentBackendPage(['func' => 'disable'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_disable') . '</a>';
} else {
    $fragment = new rex_fragment();

    if (empty($func)) {
        $content = '<p>' . $this->i18n('2factor_auth_page_instruction') . '</p>';
        $buttons = '<a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'setup'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_setup') . '</a>';
    } elseif ($func === 'setup') {
        $content = $fragment->parse('2fa.setup.config.php');

        $buttons = '<a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'verify'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_setup_verify') . '</a>';
    } elseif ($func === 'verify') {
        $otp = rex_post('rex_login_otp', 'string', null);

        $fragment->setVar('csrfToken', $csrfToken, false);
        $content = $fragment->parse('2fa.setup.verification.php');
    } else {
        throw new Exception('unknown state');
    }

    $fragment = new rex_fragment();
    $fragment->setVar('heading', "Setup", false);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    echo $fragment->parse('core/page/section.php');
}
