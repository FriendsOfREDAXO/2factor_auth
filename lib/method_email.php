<?php

namespace FriendsOfREDAXO\TwoFactorAuth;

use OTPHP\Factory;
use OTPHP\TOTP;
use rex;
use rex_addon;
use rex_mailer;
use rex_user;

/**
 * @internal
 */
final class method_email implements method_interface
{
    public function challenge(string $provisioningUrl, rex_user $user): void
    {
        $mail = new rex_mailer();

        $otp = Factory::loadFromProvisioningUri($provisioningUrl);
        $otpCode = $otp->at(time());

        $mail->addAddress($user->getEmail());
        $mail->Subject = '2FA-Code: ' . rex::getServerName() . ' (' . $_SERVER['HTTP_HOST'] . ')';
        $mail->isHTML();
        $mail->Body = '<style>body { font-size: 1.2em; text-align: center;}</style><h2>' . rex::getServerName() . ' Login verification</h2><br><h3><strong>' . $otpCode . '</strong></h3><br> is your 2 factor authentication code.';
        $mail->AltBody = rex::getServerName() . " Login verification \r\n ------------------ \r\n" . $otpCode . "\r\n ------------------ \r\nis your 2 factor authentication code.";
        if (!$mail->send()) {
            throw new exception('Unable to send E-Mail. Please try again of contact the administrator.');
        }
    }

    public static function getPeriod(): int
    {
        return (int) rex_addon::get('2factor_auth')->getConfig('email_period', 300);
    }

    public static function getloginTries(): int
    {
        return 10;
    }

    public function verify(string $provisioningUrl, string $otp): bool
    {
        $TOTP = Factory::loadFromProvisioningUri($provisioningUrl);

        // re-create from an existant uri
        if ($TOTP->verify($otp)) {
            return true;
        }

        $lastOTPCode = $TOTP->at(time() - self::getPeriod());
        if ($lastOTPCode == $otp) {
            return Factory::loadFromProvisioningUri($provisioningUrl)->verify($TOTP->at(time()));
        }

        // TODO: Secureproblem
        // Unendliche Codeversuche im Moment möglich

        // - was mache ich bei mehreren Fehleingaben? Im Moment ist das unsicher
        // - wie mache ich es, wenn es Änderungen bei den Einstellungen gibt?
        //   - solle alle E-Mail provisionalURLs neu generiert werden

        //  gitlab - account wird gelockt nach 10 fehlversuchen
        //  nach 10 minuten darf man dann wieder loslegen

        // rex_user
        // lasttrydate
        // lastlogin
        //    backend_login_policy:
        //      login_tries_until_blocked: 50
        //      login_tries_until_delay: 3
        //      relogin_delay: 5

        return false;
    }

    public function getProvisioningUri(rex_user $user): string
    {
        // create a uri with a random secret
        $otp = TOTP::create(null, self::getPeriod());

        // the label rendered in "Google Authenticator" or similar app
        $label = $user->getLogin() . '@' . rex::getServerName() . ' (' . $_SERVER['HTTP_HOST'] . ')';
        $label = str_replace(':', '_', $label); // colon is forbidden
        $otp->setLabel($label);
        $otp->setParameter('period', self::getPeriod());
        $otp->setIssuer(str_replace(':', '_', $user->getLogin()));

        return $otp->getProvisioningUri();
    }
}
