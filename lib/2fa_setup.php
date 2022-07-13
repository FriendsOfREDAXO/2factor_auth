<?php

/**
 * @internal
 */
final class rex_2fa_setup
{
    /**
     * @return void
     */
    public static function install() {
        $config = rex_string::yamlDecode(rex_file::get(rex_path::coreData("config.yml")));

        // add as setup_addon, to make sure 2factor runs in safe_mode.
        // otherwise 2fa could be worked arround by a admin user.
        if (false === array_search("2factor_auth", $config['setup_addons'])) {
            $config['setup_addons'][] = "2factor_auth";

            rex_file::put(rex_path::coreData("config.yml"), rex_string::yamlEncode($config));
        }
    }

    /**
     * @return void
     */
    public static function uninstall() {
        $config = rex_string::yamlDecode(rex_file::get(rex_path::coreData("config.yml")));

        $key = array_search("2factor_auth", $config['setup_addons']);
        if ($key !== false) {
            unset($config['setup_addons'][$key]);
            rex_file::put(rex_path::coreData("config.yml"), rex_string::yamlEncode($config));
        }
    }
}
