<?php

namespace rex_2fa;

use rex_user;

/**
 * @internal
 */
interface method_interface
{
    /**
     * @return void
     * @throws exception
     */
    public function challenge(string $provisioningUrl, rex_user $user);

    /**
     * @return bool
     * @throws exception
     */
    public function verify(string $provisioningUrl, string $otp);

    /**
     * @return string
     */
    public function getProvisioningUri(rex_user $user);
}
