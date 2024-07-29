<?php

namespace FriendsOfREDAXO\TwoFactorAuth;

use rex_user;

/**
 * @internal
 */
interface method_interface
{
    /**
     * @throws exception
     */
    public function challenge(string $provisioningUrl, rex_user $user): void;

    /**
     * @throws exception
     */
    public function verify(string $provisioningUrl, string $otp): bool;

    public function getProvisioningUri(rex_user $user): string;

    public static function getPeriod(): int;

    public static function getloginTries();
}
