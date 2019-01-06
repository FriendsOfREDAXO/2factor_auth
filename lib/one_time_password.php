<?php
use OTPHP\TOTP;

final class rex_one_time_password {
    use rex_singleton_trait;

    public function getProvisioningUri() {
        return $this->totp()->getProvisioningUri();
    }

    public function verify($otp) {
        $verified = $this->totp()->verify($otp);

        if ($verified) {
            rex_set_session('otp_verified', true);
        }

        return $verified;
    }

    private function totp() {
        $secret = "EZUQQQILCA4C4EL7LRV6O5G4SVOT5D7TBGHDARVM3QTWD4PM7LZBVTCLD4VLQBJSFYU2II32A42TZDXNFJ2RJZKNBIJB6V3N6VNIUCY";
        $otp = TOTP::create($secret);
        $user = rex::getUser();
        $otp->setLabel($user->getLogin().'@'.rex::getServername() . ' ('. $_SERVER['HTTP_HOST'] .')');
        return $otp;
    }

    static public function verified() {
        return rex_session('otp_verified', 'boolean', false);
    }

    static public function required() {
        // TODO
        return true;
    }
}