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

    const ENFORCED_ALL = 'all';
    const ENFORCED_ADMINS = 'admins_only';
    const ENFORCED_DISABLED = 'disabled';

    /**
     * @var totp_method
     */
    private $method;

    public function __construct() {
        $this->method = new totp_method();
    }

    /**
     * @param string $otp
     * @return bool
     */
    public function verify($otp)
    {
        $uri = str_replace("&amp;", "&", (string) one_time_password_config::forCurrentUser()->provisioningUri);

        $verified = $this->method->verify($uri, $otp);

        if ($verified) {
            rex_set_session('otp_verified', true);
        }

        return $verified;
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
     * @param self::ENFORCE* $enforce
     *
     * @return void
     */
    public function enforce($enforce)
    {
        rex_config::set('2factor_auth', 'enforce', $enforce);
    }

    /**
     * @return self::ENFORCE*
     */
    public function isEnforced()
    {
        return rex_config::get('2factor_auth', 'enforce', self::ENFORCED_DISABLED);
    }

    /**
     * @return totp_method
     */
    public function getMethod() {
        return $this->method;
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
