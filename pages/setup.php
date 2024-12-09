<?php

use FriendsOfREDAXO\TwoFactorAuth\method_email;
use FriendsOfREDAXO\TwoFactorAuth\method_totp;
use FriendsOfREDAXO\TwoFactorAuth\one_time_password;
use FriendsOfREDAXO\TwoFactorAuth\one_time_password_config;

/** @var rex_addon $this */

$fragment = new rex_fragment();
$message = '';
$title = $this->i18n('2fa_setup');
$buttons = '';
$content = '';
$uri = '';
$success = false;

$csrfToken = rex_csrf_token::factory('2factor_auth_setup');
$func = rex_request('func', 'string');

$otp = one_time_password::getInstance();
$otp_options = $otp->getAuthOption();

if (one_time_password::OPTION_EMAIL == $otp_options) {
    // email_only -> kein totp
    echo rex_view::info($this->i18n('2factor_auth_select_' . one_time_password::OPTION_EMAIL));
    if ('setup-totp' == $func) {
        $func = '';
    }
}

if (one_time_password::OPTION_TOTP == $otp_options) {
    // only email email_only
    echo rex_view::info($this->i18n('2factor_auth_select_' . one_time_password::OPTION_EMAIL));
    if ('setup-email' == $func) {
        $func = '';
    }
}

if ('setup-email' === $func || 'setup-totp' === $func) {
    switch ($func) {
        case 'setup-email':
            $otpMethod = new method_email();
            break;
        default:
            $otpMethod = new method_totp();
            break;
    }

    $config = one_time_password_config::loadFromDb($otpMethod, rex::requireUser());
    $config->updateMethod($otpMethod);
    $user_id = rex::requireUser()->getId();
    rex_user::clearInstance($user_id);
    rex::setProperty('user', rex_user::get($user_id));
}

$otpMethod = $otp->getMethod();

if ('' !== $func && !$csrfToken->isValid()) {
    $message = rex_view::error($this->i18n('csrf_token_invalid'));
    $func = '';
}

if ('disable' === $func) {
    $config = one_time_password_config::loadFromDb($otpMethod, rex::requireUser());
    $config->disable();
    $func = '';
}

if (one_time_password::ENFORCED_ALL === $otp->isEnforced()) {
    $message .= rex_view::info($this->i18n('2fa_enforced') . ': ' . $this->i18n('2factor_auth_enforce_' . one_time_password::ENFORCED_ALL));
}
if (one_time_password::ENFORCED_ADMINS === $otp->isEnforced()) {
    $message .= rex_view::info($this->i18n('2fa_enforced') . ': ' . $this->i18n('2factor_auth_enforce_' . one_time_password::ENFORCED_ADMINS));
}

$config = one_time_password_config::loadFromDb($otpMethod, rex::requireUser());
if ($otp->isEnabled() && $config->enabled) {
    $title = $this->i18n('status');
    switch ($config->method) {
        case 'email':
            $content = '<p>' . $this->i18n('2fa_status_email_info', rex::getUser()->getLogin(), rex::getUser()->getEmail()) . '</p>';
            break;
        default:
            $content = '<p>' . $this->i18n('2fa_status_otp_info', rex::getUser()->getLogin()) . '</p>';
            break;
    }
    $this->i18n('2fa_status_otp_instruction');
    $content .= '<p><a class="btn btn-delete" href="' . rex_url::currentBackendPage(['func' => 'disable'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_disable', rex::getUser()->getLogin()) . '</a></p>';
} else {
    if ('' === $func) {
        if (one_time_password::OPTION_ALL == $otp_options || one_time_password::OPTION_TOTP == $otp_options) {
            $content .= '<p>' . $this->i18n('2factor_auth_2fa_page_totp_instruction') . '</p>';
            $content .= '<p><a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'setup-totp'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_setup_start_totp') . '</a></p>';
        }
        if (one_time_password::OPTION_ALL == $otp_options || one_time_password::OPTION_EMAIL == $otp_options) {
            $content .= '<p>' . $this->i18n('2factor_auth_2fa_page_email_instruction') . '</p>';
            $content .= '<p><a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'setup-email'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_setup_start_email') . '</a></p>';
        }
    } elseif ('setup-totp' === $func) {
        // nothing todo
    } elseif ('setup-email' === $func) {
        if (!rex_addon::get('phpmailer')->isAvailable()) {
            $content = rex_view::error($this->i18n('2fa_setup_start_phpmailer_required'));
            $func = '';
        }

        $email = trim(rex::requireUser()->getEmail());
        if ('' !== $func && ('' === $email || !str_contains($email, '@'))) {
            $content = rex_view::error($this->i18n('2fa_setup_start_email_required'));
            $buttons = '<a class="btn btn-setup" href="' . rex_url::backendPage('profile') . '">' . $this->i18n('2fa_setup_start_email_open_profile') . '</a>';

            $func = '';
        }

        if ('' !== $func) {
            try {
                one_time_password::getInstance()->challenge();
                $message = rex_view::info($this->i18n('2fa_setup_start_email_send'));
            } catch (Exception $e) {
                $message = rex_view::error($e->getMessage());
                $func = '';
            }
        }
    } elseif ('verify-totp' === $func || 'verify-email' === $func) {
        $otp = rex_post('rex_login_otp', 'string', '');

        if ('' !== $otp) {
            if (one_time_password::getInstance()->verify($otp)) {
                $message = '<div class="alert alert-success">' . $this->i18n('2fa_setup_successfull') . '</div>';
                $config = one_time_password_config::loadFromDb($otpMethod, rex::requireUser());
                $config->enable();
                $content = '';
                $success = true;
            }
        }

        if (!$success) {
            $message = '<div class="alert alert-warning">' . $this->i18n('2fa_wrong_opt') . '</div>';
        }
    } else {
        throw new rex_exception('unknown state');
    }
}

if ('setup-email' === $func || 'verify-email' === $func || 'setup-totp' === $func || 'verify-totp' === $func) {
    $config = one_time_password_config::loadFromDb($otpMethod, rex::requireUser());
    $uri = $config->provisioningUri;

    $fragment->setVar('addon', $this, false);
    $fragment->setVar('csrfToken', $csrfToken, false);
    $fragment->setVar('message', $message, false);
    $fragment->setVar('buttons', $buttons, false);
    $fragment->setVar('uri', $uri, false);
    $fragment->setVar('success', $success, false);

    if ('setup-totp' === $func || 'verify-totp' === $func) {
        echo $fragment->parse('2fa.setup-totp.php');
    } else {
        echo $fragment->parse('2fa.setup-email.php');
    }
} else {
    $fragment = new rex_fragment();
    $fragment->setVar('before', $message, false);
    $fragment->setVar('heading', $title, false);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    echo $fragment->parse('core/page/section.php');
}
