<?php

require_once 'vendor/autoload.php';

if (rex::isBackend() && rex::getUser() && rex_one_time_password::required()) {
    if (!rex_one_time_password::verified()) {
        rex_be_controller::setCurrentPage('2factor_auth_verify');
    }
}
