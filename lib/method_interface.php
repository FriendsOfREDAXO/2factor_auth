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
     * @return void
     */
    public function challenge(string $provisioningUrl, rex_user $user);

    /**
     * @throws exception
     * @return bool
     */
    public function verify(string $provisioningUrl, string $otp);

    /**
     * @return string
     */
    public function getProvisioningUri(rex_user $user);
}
