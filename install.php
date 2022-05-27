<?php

rex_sql_table::get(rex::getTable('user'))
    ->ensureColumn(new rex_sql_column('one_time_password_config', 'text'), 'revision')
->ensure();

$config = rex_string::yamlDecode(rex_file::get(rex_path::coreData("config.yml")));

if (!array_search("2factor_auth", $config['setup_addons'])) {
    $config['setup_addons'][] = "2factor_auth";
    dump($config);
}

one_time_password::install();
