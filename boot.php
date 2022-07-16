<?php

use rex_2fa\enforce_system_setting;
use rex_2fa\one_time_password;

require_once 'vendor/autoload.php';

$addon = rex_addon::get('2factor_auth');

if (rex::isBackend() && null !== rex::getUser()) {
    if ('system' === rex_be_controller::getCurrentPagePart(1)) {
        rex_system_setting::register(new enforce_system_setting());
    }

    if ('2factor_auth' === rex_be_controller::getCurrentPagePart(1)) {
        rex_view::addJsFile($addon->getAssetsUrl('qrious.min.js'));
        rex_view::addJsFile($addon->getAssetsUrl('clipboard-copy-element.js'));
    }

    $otp = one_time_password::getInstance();

    // den benutzer auf das setup leiten, weil erwzungen aber noch nicht durchgefuehrt
    if (!$otp->isEnabled()) {
        if (one_time_password::ENFORCED_ALL === $otp->isEnforced() ||
            one_time_password::ENFORCED_ADMINS === $otp->isEnforced() && rex::getUser()->isAdmin()) {
            if ('2factor_auth' !== rex_be_controller::getCurrentPagePart(1)) {
                rex_be_controller::setCurrentPage('2factor_auth/setup');
                return;
            }
        }
    }

    // den benutzer zur einmal passwort eingabe leiten, weil one-time-passwort aktiv
    // und bisher fuer die session noch nicht eingegeben
    if ($otp->isEnabled()) {
        if (!$otp->isVerified()) {
            rex_extension::register('PAGE_BODY_ATTR', static function (rex_extension_point $ep) {
                $attributes = $ep->getSubject();
                /** add rex-page-login id */
                $attributes['id'] = ['rex-page-login'];

                /** remove rex-is-logged-in class */
                $attributes['class'] = array_filter($attributes['class'], static function ($class) {
                    return 'rex-is-logged-in' !== $class;
                });

                $ep->setSubject($attributes);
            });

            rex_be_controller::setCurrentPage('2factor_auth_verify');
        }
    }
}
