<?php

use OTPHP\TOTP;

/**
 * @internal
 */
final class rex_one_time_password_config
{
    public $provisioningUri = null;
    public $enabled = false;

    public static function forCurrentUser()
    {
        $user = rex::getUser();
        return self::fromJson($user->getValue('one_time_password_config'));
    }

    public static function loadFromDb()
    {
        $user = rex::getUser();

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

    private function init()
    {
        $user = rex::getUser();

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

    public function enable()
    {
        $this->init();
        $this->enabled = true;

        return $this->save();
    }

    public function disable()
    {
        $this->enabled = false;
        $this->provisioningUri = null;

        return $this->save();
    }

    private function save()
    {
        $user = rex::getUser();

        $userSql = rex_sql::factory();
        $userSql->setTable(rex::getTablePrefix() . 'user');
        $userSql->setWhere(['id' => $user->getId()]);
        $userSql->setValue('one_time_password_config', json_encode(['provisioningUri' => $this->provisioningUri, 'enabled' => $this->enabled]));
        $userSql->addGlobalUpdateFields();
        return $userSql->update();
    }
}
