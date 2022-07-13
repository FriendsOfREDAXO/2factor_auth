<?php

namespace rex_2fa;

use rex_user;

/**
 * @internal
 */
interface method_interface
{
    /**
     * @param string $provisioningUrl
     * @return void
     */
    public function challenge($provisioningUrl, rex_user $user);

    /**
     * @param string $provisioningUrl
     * @param string $otp
     * @return bool
     */
    public function verify($provisioningUrl, $otp);

    /**
     * @return string
     */
    public function getProvisioningUri(rex_user $user);
}
