<?php

namespace FriendsOfREDAXO\TwoFactorAuth;

use rex;
use rex_sql;

/**
 * @internal
 */
final class one_time_password_config
{
    /**
     * @var string|null
     */
    public $provisioningUri;
    /**
     * @var bool
     */
    public $enabled = false;
    /**
     * @var 'totp'|'email'|null
     */
    public $method;
    /**
     * @var \rex_user
     */
    public $user;

    public function __construct(\rex_user $user)
    {
        $this->user = $user;
    }

    /**
     * @return self
     */
    public static function forCurrentUser()
    {
        return self::forUser( rex::getImpersonator() ?? rex::requireUser());
    }

    /**
     * @return self
     */
    public static function forUser(\rex_user $user)
    {
        return self::fromJson($user->getValue('one_time_password_config'), $user);
    }

    /**
     * @return self
     */
    public static function loadFromDb(method_interface $method, \rex_user $user): self
    {
        // get non-cached values
        $userSql = rex_sql::factory();
        $userSql->setTable(rex::getTablePrefix() . 'user');
        $userSql->setWhere(['id' => $user->getId()]);
        $userSql->select();

        $json = (string) $userSql->getValue('one_time_password_config');
        $config = self::fromJson($json, $user);
        $config->init($method);
        return $config;
    }

    /**
     * @param string|null $json
     * @return self
     */
    private static function fromJson($json, \rex_user $user)
    {
        if (\is_string($json)) {
            $configArr = json_decode($json, true);

            if (\is_array($configArr)) {
                // compat with older versions, which did not yet define a method
                if (!\array_key_exists('method', $configArr)) {
                    $configArr['method'] = 'totp';
                }

                $config = new self($user);
                $config->provisioningUri = $configArr['provisioningUri'];
                $config->enabled = $configArr['enabled'];
                $config->method = $configArr['method'];
                return $config;
            }
        }

        $default = new self($user);
        $default->init(new method_totp());
        return $default;
    }

    /**
     * @return void
     */
    private function init(method_interface $method)
    {
        $this->method = $method instanceof method_email ? 'email' : 'totp';
        if (null === $this->provisioningUri) {
            $this->provisioningUri = $method->getProvisioningUri($this->user);
        }

        $this->save();
    }

    /**
     * @return void
     */
    public function enable()
    {
        $this->enabled = true;

        if (null === $this->provisioningUri) {
            throw new exception('Missing provisioning url');
        }
        if (null === $this->method) {
            throw new exception('Missing method');
        }

        $this->save();
    }

    /**
     * @return void
     */
    public function disable()
    {
        $this->enabled = false;
        $this->provisioningUri = null;

        $this->save();
    }

    /**
     * @return void
     */
    public function updateMethod(method_interface $method)
    {
        $this->method = $method instanceof method_email ? 'email' : 'totp';
        $this->provisioningUri = $method->getProvisioningUri($this->user);
        $this->save();
    }

    /**
     * @return void
     */
    private function save()
    {
        $userSql = rex_sql::factory();
        $userSql->setTable(rex::getTablePrefix() . 'user');
        $userSql->setWhere(['id' => $this->user->getId()]);
        $userSql->setValue('one_time_password_config', json_encode(
            [
                'provisioningUri' => $this->provisioningUri,
                'method' => $this->method,
                'enabled' => $this->enabled,
            ]
        ));
        $userSql->addGlobalUpdateFields();
        $userSql->update();
    }
}
