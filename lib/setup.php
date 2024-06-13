<?php

namespace FriendsOfREDAXO\TwoFactorAuth;

use rex_file;
use rex_path;
use rex_string;

use function array_search;

/**
 * @internal
 */
final class setup
{
    /**
     * @return void
     */
    public static function install()
    {
        /** @var string $coreConfig */
        $coreConfig = rex_file::get(rex_path::coreData('config.yml'));
        $config = rex_string::yamlDecode($coreConfig);

        // add as setup_addon, to make sure 2factor runs in safe_mode.
        // otherwise 2fa could be worked arround by a admin user.
        $key = array_search('2factor_auth', $config['setup_addons'], true);
        if (false === $key) {
            $config['setup_addons'][] = '2factor_auth';

            rex_file::put(rex_path::coreData('config.yml'), rex_string::yamlEncode($config));
        }
    }

    /**
     * @return void
     */
    public static function uninstall()
    {
        /** @var string $coreConfig */
        $coreConfig = rex_file::get(rex_path::coreData('config.yml'));
        $config = rex_string::yamlDecode($coreConfig);

        $key = array_search('2factor_auth', $config['setup_addons'], true);
        if (false !== $key) {
            unset($config['setup_addons'][$key]);
            rex_file::put(rex_path::coreData('config.yml'), rex_string::yamlEncode($config));
        }
    }
}
