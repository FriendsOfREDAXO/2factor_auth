<?php

use OTPHP\Factory;

/**
 * @internal
 */
final class rex_one_time_password
{
    use rex_singleton_trait;

    public function getProvisioningUri()
    {
        return $this->totp()->getProvisioningUri();
    }

    public function verify($otp)
    {
        $verified = $this->totp()->verify($otp);

        if ($verified) {
            rex_set_session('otp_verified', true);
        }

        return $verified;
    }

    private function totp()
    {
        $uri = str_replace("&amp;", "&", rex_one_time_password_config::forCurrentUser()->provisioningUri);

        // re-create from an existant uri
        return Factory::loadFromProvisioningUri($uri);
    }

    public function verified()
    {
        return rex_session('otp_verified', 'boolean', false);
    }

    public function enabled()
    {
        return rex_one_time_password_config::forCurrentUser()->enabled;
    }

}
