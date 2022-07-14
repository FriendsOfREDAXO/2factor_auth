<?php

namespace rex_2fa;

use OTPHP\Factory;
use OTPHP\TOTP;
use rex_mailer;
use rex;
use rex_user;
use function str_replace;

/**
 * @internal
 */
final class method_email implements method_interface
{
    /**
     * @param string $provisioningUrl
     * @return void
     */
    public function challenge($provisioningUrl, rex_user $user) {
        $mail = new rex_mailer();

        $otp = Factory::loadFromProvisioningUri($provisioningUrl);
        $otpCode = $otp->at(time());

        $mail->addAddress($user->getEmail());
        $mail->Subject = '2FA-Code: '. rex::getServerName() . ' (' . $_SERVER['HTTP_HOST'] . ')';
        $mail->isHTML( true );
        $mail->Body = '<style>body { font-size: 1.2em; text-align: center;}</style><h2>'.rex::getServerName().' Login verification</h2><br><h3><strong>'. $otpCode . '</strong></h3><br> is your 2 factor authentication code.';
	$mail->AltBody = rex::getServerName()." Login verification \r\n ------------------ \r\n". $otpCode . "\r\n ------------------ \r\nis your 2 factor authentication code.";

        if (!$mail->send()) {
            throw new \rex_exception('Unable to send e-mail. Make sure to setup the phpmailer AddOn.');
        }
    }

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
        $otp = TOTP::create(null, 60);

        // the label rendered in "Google Authenticator" or similar app
        $label = $user->getLogin() . '@' . rex::getServerName() . ' (' . $_SERVER['HTTP_HOST'] . ')';
        $label = str_replace(':', '_', $label); // colon is forbidden
        $otp->setLabel($label);
        $otp->setIssuer(str_replace(':', '_', $user->getLogin()));

        return $otp->getProvisioningUri();
    }
}
