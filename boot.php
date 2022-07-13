<?php

use rex_2fa\one_time_password;
use rex_2fa\enforce_system_setting;

require_once 'vendor/autoload.php';

$addon = rex_addon::get('2factor_auth');

if (rex::isBackend() && rex::getUser() !== null) {
    if ('system' === rex_be_controller::getCurrentPagePart(1)) {
        rex_system_setting::register(new enforce_system_setting());
    }

    if ('2factor_auth' === rex_be_controller::getCurrentPagePart(1)) {
        rex_view::addJsFile($addon->getAssetsUrl('qrious.min.js'));
        rex_view::addJsFile($addon->getAssetsUrl('clipboard-copy-element.js'));
    }


    $otp = one_time_password::getInstance();

    if ($otp->isEnforced()) {
        if (!$otp->isEnabled()) {
            if ('2factor_auth' !== rex_be_controller::getCurrentPagePart(1)) {
               rex_be_controller::setCurrentPage('2factor_auth/setup');
            }
            return;
        }
    }

    if ($otp->isEnabled()) {
        if (!$otp->isVerified()) {
            rex_extension::register('PAGE_BODY_ATTR', static function (rex_extension_point $ep) {
                $attributes = $ep->getSubject();
                /** add rex-page-login id */
                $attributes['id'] = ['rex-page-login'];

                /** remove rex-is-logged-in class */
                $attributes['class'] = array_filter($attributes['class'], static function($class) {
                    return $class !== 'rex-is-logged-in';
                });

                $ep->setSubject($attributes);
            });

            rex_be_controller::setCurrentPage('2factor_auth_verify');
        }
    }
}
