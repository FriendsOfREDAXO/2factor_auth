<?php

namespace rex_2fa;

use OTPHP\TOTP;
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
    public static function loadFromDb()
    {
        $user = rex::requireUser();

        // get non-cached values
        $userSql = rex_sql::factory();
        $userSql->setTable(rex::getTablePrefix() . 'user');
        $userSql->setWhere(['id' => $user->getId()]);
        $userSql->select();

        $json = $userSql->getValue('one_time_password_config');
        $config = self::fromJson($json);
        $config->init();
        return $config;
    }

    /**
     * @param string|null $json
     * @return self
     */
    private static function fromJson($json)
    {
        if ($json) {
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
    private function init()
    {
        $user = rex::requireUser();

        if (empty($this->provisioningUri)) {
            // create a uri with a random secret
            $otp = TOTP::create();
            // the label rendered in "Google Authenticator" or similar app

            $label = $user->getLogin() . '@' . rex::getServername() . ' (' . $_SERVER['HTTP_HOST'] . ')';
            $label = str_replace(':', '_', $label); // colon is forbidden
            $otp->setLabel($label);
            $otp->setIssuer(str_replace(':', '_', $user->getLogin()));

            $this->provisioningUri = $otp->getProvisioningUri();

            $this->save();
        }
    }

    /**
     * @return void
     */
    public function enable()
    {
        $this->init();
        $this->enabled = true;

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
        $userSql->setValue('one_time_password_config', json_encode(['provisioningUri' => $this->provisioningUri, 'enabled' => $this->enabled]));
        $userSql->addGlobalUpdateFields();
        $userSql->update();
    }
}
