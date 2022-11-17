<?php

rex_sql_table::get(rex::getTable('user'))
    ->removeColumn('one_time_password_config')
->ensure();

FriendsOfREDAXO\TwoFactorAuth\setup::uninstall();
