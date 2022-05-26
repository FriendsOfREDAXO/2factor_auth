<?php

require_once 'vendor/autoload.php';

if (rex::isBackend() && rex::getUser()) {
    $otp = rex_one_time_password::getInstance();
    if ($otp->enabled()) {
        if (!$otp->verified()) {
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
