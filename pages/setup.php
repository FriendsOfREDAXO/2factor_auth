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

// waehrend einer impersonation gehoert der code dem real angemeldeten benutzer,
// die 2fa eines fremden benutzers laesst sich hier deshalb nicht einrichten.
if (null !== rex::getImpersonator()) {
    echo rex_view::warning($this->i18n('2fa_impersonation_unsupported'));

    return;
}

$user = rex::requireUser();

$otp = one_time_password::getInstance();
$otp_options = $otp->getAuthOption();

$otpEnforcedForUser = one_time_password::ENFORCED_ALL === $otp->isEnforced()
    || one_time_password::ENFORCED_ADMINS === $otp->isEnforced() && $user->isAdmin();

if (one_time_password::OPTION_EMAIL == $otp_options) {
    // email_only -> kein totp
    echo rex_view::info($this->i18n('2factor_auth_select_' . one_time_password::OPTION_EMAIL));
    if ('setup-totp' == $func) {
        $func = '';
    }
}

if (one_time_password::OPTION_TOTP == $otp_options) {
    // totp_only -> keine email
    echo rex_view::info($this->i18n('2factor_auth_select_' . one_time_password::OPTION_TOTP));
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

    $config = one_time_password_config::loadFromDb($otpMethod, $user);
    $config->updateMethod($otpMethod);
    $user_id = $user->getId();
    rex_user::clearInstance($user_id);
    $user = rex_user::get($user_id);
    rex::setProperty('user', $user);
}

$otpMethod = $otp->getMethod();

if ('' !== $func && !$csrfToken->isValid()) {
    $message = rex_view::error($this->i18n('csrf_token_invalid'));
    $func = '';
}

if ('disable' === $func) {
    $config = one_time_password_config::loadFromDb($otpMethod, $user);

    if (!$config->enabled || $otpEnforcedForUser) {
        // nichts zu deaktivieren bzw. deaktivieren ist nicht erlaubt
        $func = '';
    } else {
        // das deaktivieren muss mit einem gueltigen code bestaetigt werden,
        // damit eine uebernommene session die 2fa nicht einfach abschalten kann.
        $blockedSeconds = $otp->getBlockedSeconds();
        $otpInput = rex_post('rex_login_otp', 'string', null);

        if ($blockedSeconds > 0) {
            $message .= rex_view::error(rex_i18n::rawMsg('one_time_password_too_many_tries', $blockedSeconds, $otpMethod::getloginTries(), $otpMethod::getPeriod()));
        } elseif (null !== $otpInput && '' !== $otpInput) {
            if ($otp->verify($otpInput)) {
                $otp->resetFailedAttempts();
                $config->disable();

                $message .= rex_view::success($this->i18n('2fa_disabled'));
                $func = '';
            } else {
                $otp->registerFailedAttempt();
                $message .= rex_view::error($this->i18n('2fa_otp_wrong'));
            }
        } elseif ($otpMethod instanceof method_email) {
            try {
                $message .= rex_view::info($otp->challenge()
                    ? $this->i18n('2fa_info_email_sent')
                    : $this->i18n('2fa_info_email_still_valid'));
            } catch (Exception $e) {
                $message .= rex_view::error($e->getMessage());
            }
        }
    }

    if ('disable' === $func) {
        $content = '<p>' . $this->i18n('2fa_disable_verify_instruction') . '</p>';
        $content .= '
        <form action="' . rex_url::currentBackendPage() . '" method="post">
            ' . $csrfToken->getHiddenField() . '
            <input type="hidden" name="func" value="disable" />
            <dl class="rex-form-group form-group">
                <dt><label for="rex_login_otp">' . rex_i18n::msg('2factor_auth_2fa_one_time_password') . '</label></dt>
                <dd><input class="form-control" type="text" id="rex_login_otp" name="rex_login_otp" value="" autocomplete="off" autofocus></dd>
            </dl>
            <button class="btn btn-delete" type="submit">' . $this->i18n('2fa_disable_verify_action') . '</button>
            <a class="btn btn-abort" href="' . rex_url::currentBackendPage() . '">' . rex_i18n::msg('form_abort') . '</a>
        </form>';

        $fragment = new rex_fragment();
        $fragment->setVar('before', $message, false);
        $fragment->setVar('heading', $this->i18n('2fa_disable', rex_escape($user->getLogin())), false);
        $fragment->setVar('body', $content, false);
        echo $fragment->parse('core/page/section.php');

        return;
    }
}

if (one_time_password::ENFORCED_ALL === $otp->isEnforced()) {
    $message .= rex_view::info($this->i18n('2fa_enforced') . ': ' . $this->i18n('2factor_auth_enforce_' . one_time_password::ENFORCED_ALL));
}
if (one_time_password::ENFORCED_ADMINS === $otp->isEnforced()) {
    $message .= rex_view::info($this->i18n('2fa_enforced') . ': ' . $this->i18n('2factor_auth_enforce_' . one_time_password::ENFORCED_ADMINS));
}

$config = one_time_password_config::loadFromDb($otpMethod, $user);
if ($otp->isEnabled() && $config->enabled) {
    $title = $this->i18n('status');
    switch ($config->method) {
        case 'email':
            $content = '<p>' . $this->i18n('2fa_status_email_info', $user->getLogin(), $user->getEmail()) . '</p>';
            break;
        default:
            $content = '<p>' . $this->i18n('2fa_status_otp_info', $user->getLogin()) . '</p>';
            break;
    }
    // bei erzwungener 2fa gibt es nichts abzuschalten
    if (!$otpEnforcedForUser) {
        $content .= '<p><a class="btn btn-delete" href="' . rex_url::currentBackendPage(['func' => 'disable'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_disable', rex_escape($user->getLogin())) . '</a></p>';
    }
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

        $email = trim($user->getEmail());
        if ('' !== $func && ('' === $email || !str_contains($email, '@'))) {
            $content = rex_view::error($this->i18n('2fa_setup_start_email_required'));
            $buttons = '<a class="btn btn-setup" href="' . rex_url::backendPage('profile') . '">' . $this->i18n('2fa_setup_start_email_open_profile') . '</a>';

            $func = '';
        }

        if ('' !== $func) {
            try {
                // die provisioning-uri wurde soeben neu erzeugt, der code muss
                // deshalb in jedem fall zugestellt werden
                $otp->challenge(true);
                $message = rex_view::info($this->i18n('2fa_setup_start_email_send'));
            } catch (Exception $e) {
                $message = rex_view::error($e->getMessage());
                $func = '';
            }
        }
    } elseif ('verify-totp' === $func || 'verify-email' === $func) {
        $otpInput = rex_post('rex_login_otp', 'string', '');

        if ('' !== $otpInput) {
            if ($otp->verify($otpInput)) {
                $message = '<div class="alert alert-success">' . $this->i18n('2fa_setup_successfull') . '</div>';
                $config = one_time_password_config::loadFromDb($otpMethod, $user);
                $config->enable();

                // ein frisch eingerichteter zugang startet ohne altlasten,
                // sonst greift ggf. sofort eine sperre aus fruehreren fehlversuchen
                $otp->resetFailedAttempts();
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
    $config = one_time_password_config::loadFromDb($otpMethod, $user);
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
