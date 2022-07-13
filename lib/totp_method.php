<?php

namespace rex_2fa;

use OTPHP\Factory;
use OTPHP\TOTP;
use rex;
use rex_user;
use function str_replace;

/**
 * @internal
 */
final class totp_method
{
    /**
     * @param string $provisioningUrl
     * @param string $otp
     * @return bool
     */
    public function verify($provisioningUrl, $otp)
    {
        // re-create from an existant uri
        return Factory::loadFromProvisioningUri($provisioningUrl)->verify($otp);
    }

    /**
     * @return string
     */
    public function getProvisioningUri(rex_user $user)
    {
        // create a uri with a random secret
        $otp = TOTP::create();

        // the label rendered in "Google Authenticator" or similar app
        $label = $user->getLogin() . '@' . rex::getServerName() . ' (' . $_SERVER['HTTP_HOST'] . ')';
        $label = str_replace(':', '_', $label); // colon is forbidden
        $otp->setLabel($label);
        $otp->setIssuer(str_replace(':', '_', $user->getLogin()));

        return $otp->getProvisioningUri();
    }
}
