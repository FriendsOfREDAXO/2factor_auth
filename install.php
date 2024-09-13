<?php

use FriendsOfREDAXO\TwoFactorAuth\setup;

if (!class_exists('FriendsOfREDAXO\TwoFactorAuth\setup')) {
    include_once rex_addon::get('2factor_auth')->getPath('/lib/setup.php');
}

rex_sql_table::get(rex::getTable('user'))
    ->ensureColumn(new rex_sql_column('one_time_password_config', 'text'), 'revision')
    ->ensureColumn(new rex_sql_column('one_time_password_tries', 'tinyint'), 'one_time_password_config')
    ->ensureColumn(new rex_sql_column('one_time_password_lasttry', 'int'), 'one_time_password_tries')
->ensure();

setup::install();
