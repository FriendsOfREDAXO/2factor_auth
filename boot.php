<?php

use FriendsOfREDAXO\TwoFactorAuth\one_time_password;

$addon = rex_addon::get('2factor_auth');

if (rex::isBackend() && null !== rex::getUser()) {
    if ('2factor_auth' === rex_be_controller::getCurrentPagePart(1)) {
        rex_view::addJsFile($addon->getAssetsUrl('qrious.min.js'));
        rex_view::addJsFile($addon->getAssetsUrl('clipboard-copy-element.js'));
    }

    $otp = one_time_password::getInstance();

    // den benutzer auf das setup leiten, weil erwzungen aber noch nicht durchgefuehrt
    if (!$otp->isEnabled()) {
        if (one_time_password::ENFORCED_ALL === $otp->isEnforced()
            || one_time_password::ENFORCED_ADMINS === $otp->isEnforced() && rex::getUser()->isAdmin()) {
            rex_be_controller::setCurrentPage('2factor_auth/setup');
            return;
        }
    }

    // den benutzer zur einmal passwort eingabe leiten, weil one-time-passwort aktiv
    // und bisher fuer die session noch nicht eingegeben
    if ($otp->isEnabled()) {
        if (!$otp->isVerified()) {
            rex_extension::register('PAGES_PREPARED', static function (rex_extension_point $ep) {
                $profilePage = rex_be_controller::getCurrentPageObject('profile');
                if (!$profilePage) {
                    return;
                }
                $profilePage->setPath(rex_path::addon('2factor_auth', 'pages/verify.php'));
                $profilePage->setHasNavigation(false);
                $profilePage->setPjax(false);
                rex_extension::register('PAGE_BODY_ATTR', static function (rex_extension_point $ep) {
                    $attributes = $ep->getSubject();
                    /** add rex-page-login id */
                    $attributes['id'] = ['rex-page-login'];
                    $ep->setSubject($attributes);
                });
            });
        }
    }
}
