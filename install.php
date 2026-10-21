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

// vertrauenswuerdige geraete fuer die "eingeloggt bleiben"-funktion: haelt fest, dass
// der zweite faktor fuer einen bestimmten stay-logged-in-cookie bereits erbracht wurde
rex_sql_table::get(rex::getTable('2factor_auth_trusted_device'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('user_id', 'int(10) unsigned'))
    ->ensureColumn(new rex_sql_column('cookie_key_hash', 'varchar(64)'))
    ->ensureColumn(new rex_sql_column('verified_at', 'datetime'))
    ->ensureIndex(new rex_sql_index('cookie_key_hash', ['cookie_key_hash'], rex_sql_index::UNIQUE))
->ensure();

setup::install();
