<?php

use rex_2fa\one_time_password;
use rex_2fa\one_time_password_config;

/** @var rex_addon $this */

$fragment = new rex_fragment();
$message = '';
$buttons = '';
$content = '';
$uri = '';
$success = false;

$csrfToken = rex_csrf_token::factory('2factor_auth_setup');
$func = rex_request('func', 'string');
$otp = one_time_password::getInstance();
$otpMethod = $otp->getMethod();
$config = one_time_password_config::loadFromDb($otpMethod);

if ($func !== '' && !$csrfToken->isValid()) {
    $message = '<div class="alert alert-danger">' . $this->i18n('csrf_token_invalid') . '</div>';
    $func = '';
}

if ($func === 'disable') {
    $config = one_time_password_config::loadFromDb($otpMethod);
    $config->disable();
    $func = '';
}

if ($otp->isEnforced() === one_time_password::ENFORCED_ALL) {
    $message .= '<div class="alert alert-warning">' . $this->i18n('2fa_enforced') . ': ' . $this->i18n('2fa_enforced_yes_all') . '</div>';
}
if ($otp->isEnforced() === one_time_password::ENFORCED_ADMINS) {
    $message .= '<div class="alert alert-warning">' . $this->i18n('2fa_enforced') . ': ' . $this->i18n('2fa_enforced_yes_admins') . '</div>';
}

if ($otp->isEnabled() && $config->enabled) {
    $content = $this->i18n('2fa_active') . "<br/><br/>". $this->i18n('2fa_disable_instruction');
    $buttons = '<a class="btn btn-delete" href="' . rex_url::currentBackendPage(['func' => 'disable'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_disable') . '</a>';
}
else {
    if ($func === '') {
        $content = $this->i18n('2fa_inactive') . "<br/><br/>". $this->i18n('2factor_auth_2fa_page_instruction');
        $buttons = '<a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'setup'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_setup_start') . '</a>';
    }
    elseif ($func === 'setup') {
        //
    }
    elseif ($func === 'verify') {
        $otp = rex_post('rex_login_otp', 'string', null);

        if ($otp !== null && $otp !== '') {
            if (one_time_password::getInstance()->verify($otp)) {
                $message = '<div class="alert alert-success">' . $this->i18n('2fa_setup_successfull') . '</div>';
                $config = one_time_password_config::loadFromDb($otpMethod);
                $config->enable();
                $success = true;
            }
        }

        if (!$success) {
            $message = '<div class="alert alert-warning">' . $this->i18n('2fa_wrong_opt') . '</div>';
        }
    }
    else {
        throw new rex_exception('unknown state');
    }
}

if($func === 'setup' || $func === 'verify') {
    $config = one_time_password_config::loadFromDb($otpMethod);
    $uri = $config->provisioningUri;

    $fragment->setVar('addon', $this, false);
    $fragment->setVar('csrfToken', $csrfToken, false);
    $fragment->setVar('message', $message, false);
    $fragment->setVar('buttons', $buttons, false);
    $fragment->setVar('uri', $uri, false);
    $fragment->setVar('success', $success, false);
    echo $fragment->parse('2fa.setup.php');
}
else {
    $fragment = new rex_fragment();
    $fragment->setVar('before', $message, false);
    $fragment->setVar('heading', 'Setup', false);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    echo $fragment->parse('core/page/section.php');
}
