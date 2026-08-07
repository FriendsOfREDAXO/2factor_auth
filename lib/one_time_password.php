<?php

namespace FriendsOfREDAXO\TwoFactorAuth;

use InvalidArgumentException;
use rex;
use rex_config;
use rex_singleton_trait;
use rex_sql;
use rex_user;

use function max;
use function time;

/**
 * @internal
 */
final class one_time_password
{
    use rex_singleton_trait;

    public const ENFORCED_ALL = 'all';
    public const ENFORCED_ADMINS = 'admins_only';
    public const ENFORCED_DISABLED = 'disabled';

    public const OPTION_ALL = 'all';
    public const OPTION_TOTP = 'totp_only';
    public const OPTION_EMAIL = 'email_only';

    /** @var method_interface|null */
    private $method;

    /**
     * Stellt dem benutzer einen code zu, sofern die gewaehlte methode das erfordert.
     *
     * @param bool $force zustellung auch dann erzwingen, wenn kurz zuvor bereits ein
     *                    code zugestellt wurde (noetig nach einem wechsel des secrets)
     *
     * @return bool true, wenn ein code zugestellt wurde. false, wenn der zuletzt
     *              zugestellte code noch gueltig ist und deshalb nichts versendet wurde.
     */
    public function challenge($force = false)
    {
        $method = $this->getMethod();

        // bei der e-mail-methode nicht bei jedem seitenaufruf eine neue mail versenden,
        // innerhalb der gueltigkeitsdauer ist der code ohnehin identisch.
        if (!$force
            && $method instanceof method_email
            && rex_session('otp_challenge_time', 'int', 0) > time() - method_email::getPeriod()
        ) {
            return false;
        }

        $uri = str_replace('&amp;', '&', (string) one_time_password_config::forCurrentUser()->provisioningUri);

        $method->challenge($uri, $this->getUser());

        rex_set_session('otp_challenge_time', time());

        return true;
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
     * Verbleibende sperrzeit in sekunden, nachdem zu viele codes falsch eingegeben wurden.
     *
     * @return int 0, wenn die eingabe nicht gesperrt ist
     */
    public function getBlockedSeconds()
    {
        $method = $this->getMethod();
        $user = $this->getUser();

        if ((int) $user->getValue('one_time_password_tries') < $method::getloginTries()) {
            return 0;
        }

        $lastTry = (int) $user->getValue('one_time_password_lasttry');

        return max(0, $lastTry + $method::getPeriod() - time());
    }

    /**
     * @return void
     */
    public function registerFailedAttempt()
    {
        $this->saveTries((int) $this->getUser()->getValue('one_time_password_tries') + 1);
    }

    /**
     * @return void
     */
    public function resetFailedAttempts()
    {
        $this->saveTries(0);
    }

    /**
     * @return void
     */
    private function saveTries(int $tries)
    {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('user'));
        $sql->setWhere('id = :id', ['id' => $this->getUser()->getId()]);
        $sql->setValue('one_time_password_tries', $tries);
        $sql->setValue('one_time_password_lasttry', time());
        $sql->update();
    }

    /**
     * Bei einer impersonation ist immer der real angemeldete benutzer massgeblich.
     *
     * @return rex_user
     */
    private function getUser()
    {
        return rex::getImpersonator() ?? rex::requireUser();
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
     * @return self::OPTION*
     */
    public function getAuthOption()
    {
        return rex_config::get('2factor_auth', 'option', self::OPTION_ALL);
    }

    public function setAuthOption(string $option): void
    {
        rex_config::set('2factor_auth', 'option', $option);
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
