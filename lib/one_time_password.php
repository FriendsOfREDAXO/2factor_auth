<?php

namespace rex_2fa;

use InvalidArgumentException;
use rex;
use rex_config;
use rex_singleton_trait;
use function rex_set_session;
use function str_replace;

/**
 * @internal
 */
final class one_time_password
{
    use rex_singleton_trait;

    public const ENFORCED_ALL = 'all';
    public const ENFORCED_ADMINS = 'admins_only';
    public const ENFORCED_DISABLED = 'disabled';

    /**
     * @var method_interface|null
     */
    private $method;

    /**
     * @return void
     */
    public function challenge()
    {
        $user = rex::requireUser();

        $uri = str_replace('&amp;', '&', (string) one_time_password_config::forCurrentUser()->provisioningUri);

        $this->getMethod()->challenge($uri, $user);
    }

    /**
     * @param string $otp
     * @return bool
     */
    public function verify($otp)
    {
        $uri = str_replace('&amp;', '&', (string) one_time_password_config::forCurrentUser()->provisioningUri);

        $verified = $this->getMethod()->verify($uri, $otp);

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
     * @return method_interface
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $methodType = one_time_password_config::forCurrentUser()->method;

            if ('totp' === $methodType) {
                $this->method = new method_totp();
            } elseif ('email' === $methodType) {
                $this->method = new method_email();
            } else {
                throw new InvalidArgumentException("Unknown method: $methodType");
            }
        }

        return $this->method;
    }
}
