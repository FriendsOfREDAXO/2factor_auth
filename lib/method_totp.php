<?php

namespace FriendsOfREDAXO\TwoFactorAuth;

use OTPHP\Factory;
use OTPHP\TOTP;
use rex;
use rex_user;

use function str_replace;

/**
 * @internal
 */
final class method_totp implements method_interface
{
    public function challenge(string $provisioningUrl, rex_user $user): void
    {
        // nothing todo
    }

    public function verify(string $provisioningUrl, string $otp): bool
    {
        // re-create from an existant uri
        return Factory::loadFromProvisioningUri($provisioningUrl)->verify($otp);
    }

    public static function getPeriod(): int
    {
        // default period is 30s and digest is sha1. Google Authenticator is restricted to this settings
        return 30;
    }

    public static function getloginTries(): int
    {
        return 10;
    }

    public function getProvisioningUri(rex_user $user): string
    {
        // the label rendered in "Google Authenticator" or similar app
        $label = $user->getLogin() . '@' . rex::getServerName() . ' (' . rex_server('HTTP_HOST', 'string', '') . ')';
        $label = str_replace(':', '_', $label); // colon is forbidden

        // create a uri with a random secret
        $otp = TOTP::generate()
            ->withPeriod(self::getPeriod())
            ->withLabel($label)
            ->withIssuer(str_replace(':', '_', $user->getLogin()));

        return $otp->getProvisioningUri();
    }
}
