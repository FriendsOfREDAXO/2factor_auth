<?php
use OTPHP\TOTP;

final class rex_one_time_password_config
{
    public $provisioningUri = null;
    public $enabled = false;

    static public function forCurrentUser() {
        $user = rex::getUser();
        return self::fromJson($user->getValue('one_time_password_config'));
    }

    static public function loadFromDb() {
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

    static private function fromJson($json) {
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
            $otp->setLabel($user->getLogin() . '@' . rex::getServername() . ' (' . $_SERVER['HTTP_HOST'] . ')');

            $this->provisioningUri = $otp->getProvisioningUri();

            $this->save();
        }
    }

    public function enable() {
        $this->init();
        $this->enabled = true;

        return $this->save();
    }

    public function disable() {
        $this->enabled = false;

        return $this->save();
    }

    private function save() {
        $user = rex::getUser();

        $userSql = rex_sql::factory();
        $userSql->setTable(rex::getTablePrefix() . 'user');
        $userSql->setWhere(['id' => $user->getId()]);
        $userSql->setValue('one_time_password_config', json_encode(['provisioningUri' => $this->provisioningUri, 'enabled' => $this->enabled]));
        $userSql->addGlobalUpdateFields();
        return $userSql->update();
    }
}