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
    public static function loadFromDb(totp_method $method)
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

            $config = new self();
            $config->provisioningUri = $configArr['provisioningUri'];
            $config->enabled = $configArr['enabled'];
            return $config;
        }

        return new self();
    }

    /**
     * @return void
     */
    private function init(totp_method $method)
    {
        $user = rex::requireUser();

        if (null === $this->provisioningUri) {
            $this->provisioningUri = $method->getProvisioningUri($user);

            $this->save();
        }
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
                'enabled' => $this->enabled,
            ]
        ));
        $userSql->addGlobalUpdateFields();
        $userSql->update();
    }
}
