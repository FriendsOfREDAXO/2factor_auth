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
        $recovery_codes = self::generateRecoveryCodes();
        $user = rex::getUser();

        $userSql = rex_sql::factory();
        $userSql->setTable(rex::getTablePrefix() . 'user');
        $userSql->setWhere(['id' => $user->getId()]);
        $userSql->setValue('one_time_password_config', json_encode(['provisioningUri' => $this->provisioningUri, 'enabled' => $this->enabled]));
        $userSql->setValue('one_time_password_recovery_code', implode(",",self::generateRecoveryCodes()));
        $userSql->addGlobalUpdateFields();
        return $userSql->update();
    }
    
    private static function generateRecoveryCode() {
        return bin2hex(random_bytes(16));
    }

    public static function generateRecoveryCodes($total = 10) :array {
        
        $codes = [];
        for($i; $i++; $i < $total) {
            $codes[self::generateRecoveryCode()] = rex_login::passwordHash(self::generateRecoveryCode());
        }
        return $codes;
    }
}
