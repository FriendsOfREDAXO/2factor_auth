<?php

rex_sql_table::get(rex::getTable('user'))
    ->ensureColumn(new rex_sql_column('one_time_password_config', 'text'), 'revision')
->ensure();

rex_2fa\setup::install();
