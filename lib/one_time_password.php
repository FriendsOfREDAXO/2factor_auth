<?php

namespace rex_2fa;

use OTPHP\Factory;
use rex_singleton_trait;
use rex_config;
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
    public function isVerified()
    {
        return rex_session('otp_verified', 'boolean', false);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return one_time_password_config::forCurrentUser()->enabled;
    }

    /**
     * @return void
     */
    public function enforce(bool $enforce)
    {
        rex_config::set('2factor_auth', 'enforce', $enforce);
    }

    /**
     * @return bool
     */
    public function isEnforced()
    {
        return rex_config::get('2factor_auth', 'enforce', false);
    }

    /**
     * @return bool
     * @deprecated use isVerified() instead
     */
    public function verified()
    {
        return $this->isVerified();
    }

    /**
     * @return bool
     * @deprecated use isEnabled() instead
     */
    public function enabled()
    {
        return $this->isEnabled();
    }
}
