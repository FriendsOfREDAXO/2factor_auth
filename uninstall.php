<?php

rex_sql_table::get(rex::getTable('user'))
    ->removeColumn('one_time_password_config')
    ->removeColumn('one_time_password_tries')
    ->removeColumn('one_time_password_lasttry')
->ensure();
