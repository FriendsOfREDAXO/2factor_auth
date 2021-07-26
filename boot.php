<?php

require_once 'vendor/autoload.php';

if (rex::isBackend() && rex::getUser()) {
    $otp = rex_one_time_password::getInstance();
    if ($otp->enabled()) {
        if (!$otp->verified()) {
            rex_be_controller::setCurrentPage('2factor_auth_verify');
        }
    }
}

rex_view::addJsFile($this->getAssetsUrl('qrious.min.js'));
rex_view::addJsFile($this->getAssetsUrl('clipboard-copy-element.js'));
