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

if ($func === 'setup-email') {
    $otpMethod = new \rex_2fa\method_email();
    $config = one_time_password_config::loadFromDb($otpMethod);
}

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

$config = one_time_password_config::loadFromDb($otpMethod);
if ($otp->isEnabled() && $config->enabled) {
    $content = $this->i18n('2fa_disable_instruction');
    $buttons = '<a class="btn btn-delete" href="' . rex_url::currentBackendPage(['func' => 'disable'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_disable') . '</a>';
}
else {
    if ($func === '') {
        $content = $this->i18n('2factor_auth_2fa_page_instruction');
        $buttons = '
           <a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'setup-totp'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_setup_start_totp') . '</a>
           <a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'setup-email'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_setup_start_email') . '</a>
        ';
    }
    elseif ($func === 'setup-totp') {
        // nothing todo
    }
    elseif ($func === 'setup-email') {
        if (!rex_addon::get('phpmailer')->isAvailable()) {
            $content = rex_view::error($this->i18n('2fa_setup_start_phpmailer_required'));
            $func = '';
        }

        $email = trim(rex::requireUser()->getEmail());
        if ($func !== '' && ($email === '' || strpos($email, '@') === false)) {
            $content = rex_view::error($this->i18n('2fa_setup_start_email_required'));
            $buttons = '<a class="btn btn-setup" href="' . rex_url::backendPage('profile') . '">' . $this->i18n('2fa_setup_start_email_open_profile') . '</a>';

            $func = '';
        }

        if ($func !== '') {
            one_time_password::getInstance()->challenge();
        }
    }
    elseif ($func === 'verify-totp' || $func === 'verify-email') {
        $otp = rex_post('rex_login_otp', 'string', '');

        if ($otp !== '') {
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

if($func === 'setup-email' || $func === 'verify-email' || $func === 'setup-totp' || $func === 'verify-totp') {
    $config = one_time_password_config::loadFromDb($otpMethod);
    $uri = $config->provisioningUri;

    $fragment->setVar('addon', $this, false);
    $fragment->setVar('csrfToken', $csrfToken, false);
    $fragment->setVar('message', $message, false);
    $fragment->setVar('buttons', $buttons, false);
    $fragment->setVar('uri', $uri, false);
    $fragment->setVar('success', $success, false);

    if ($func === 'setup-totp' || $func === 'verify-totp') {
        echo $fragment->parse('2fa.setup-totp.php');
    } else {
        echo $fragment->parse('2fa.setup-email.php');
    }
}
else {
    $fragment = new rex_fragment();
    $fragment->setVar('before', $message, false);
    $fragment->setVar('heading', 'Setup', false);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    echo $fragment->parse('core/page/section.php');
}
