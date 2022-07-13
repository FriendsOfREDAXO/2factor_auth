<?php

namespace rex_2fa;

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
     * @return self
     */
    public static function forCurrentUser()
    {
        $user = rex::requireUser();
        return self::fromJson($user->getValue('one_time_password_config'));
    }

    /**
     * @return self
     */
    public static function loadFromDb(method_interface $method)
    {
        $user = rex::requireUser();

        // get non-cached values
        $userSql = rex_sql::factory();
        $userSql->setTable(rex::getTablePrefix() . 'user');
        $userSql->setWhere(['id' => $user->getId()]);
        $userSql->select();

        $json = $userSql->getValue('one_time_password_config');
        $config = self::fromJson($json);
        $config->init($method);
        return $config;
    }

    /**
     * @param string|null $json
     * @return self
     */
    private static function fromJson($json)
    {
        if (is_string($json)) {
            $configArr = json_decode($json, true);

            if (is_array($configArr)) {
                // compat with older versions, which did not yet define a method
                if (!array_key_exists('method', $configArr)) {
                    $configArr['method'] = 'totp';
                }

                $config = new self();
                $config->provisioningUri = $configArr['provisioningUri'];
                $config->enabled = $configArr['enabled'];
                $config->method = $configArr['method'];
                return $config;
            }
        }

        $default = new self();
        $default->init(new method_totp());
        return $default;
    }

    /**
     * @return void
     */
    private function init(method_interface $method)
    {
        $user = rex::requireUser();

        $this->method = $method instanceof method_email ? 'email' : 'totp';
        if (null === $this->provisioningUri) {
            $this->provisioningUri = $method->getProvisioningUri($user);
        }

        $this->save();
    }

    /**
     * @return void
     */
    public function enable()
    {
        $this->enabled = true;

        if ($this->provisioningUri === null) {
            throw new \rex_exception('Missing provisioning url');
        }
        if ($this->method === null) {
            throw new \rex_exception('Missing method');
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
    private function save()
    {
        $user = rex::requireUser();

        $userSql = rex_sql::factory();
        $userSql->setTable(rex::getTablePrefix() . 'user');
        $userSql->setWhere(['id' => $user->getId()]);
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
