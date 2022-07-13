<?php

namespace rex_2fa;

use OTPHP\Factory;
use rex_singleton_trait;
use function rex_set_session;
use function str_replace;

/**
 * @internal
 */
final class one_time_password
{
    use rex_singleton_trait;

    /**
     * @param string $otp
     * @return bool
     */
    public function verify($otp)
    {
        $verified = $this->totp()->verify($otp);

        if ($verified) {
            rex_set_session('otp_verified', true);
        }

        return $verified;
    }

    /**
     * @return \OTPHP\OTPInterface
     */
    private function totp()
    {
        $uri = str_replace("&amp;", "&", one_time_password_config::forCurrentUser()->provisioningUri);

        // re-create from an existant uri
        return Factory::loadFromProvisioningUri($uri);
    }

    /**
     * @return bool
     */
    public function verified()
    {
        return rex_session('otp_verified', 'boolean', false);
    }

    /**
     * @return bool
     */
    public function enabled()
    {
        return one_time_password_config::forCurrentUser()->enabled;
    }

}
