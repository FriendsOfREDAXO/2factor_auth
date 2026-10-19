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
            $this->rememberTrustedDevice();
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
     * Anzahl der tage, fuer die der zweite faktor auf einem "eingeloggt bleiben"-geraet
     * gemerkt wird. 0 deaktiviert die trusted-device-funktion (immer erneute abfrage).
     *
     * @return int
     */
    public function getTrustedDeviceDays()
    {
        return max(0, (int) rex_config::get('2factor_auth', 'trusted_device_days', 30));
    }

    /**
     * Markiert die aktuelle session als verifiziert, wenn das geraet ueber einen gueltigen
     * "eingeloggt bleiben"-cookie bereits den zweiten faktor erbracht hat.
     *
     * @return bool true, wenn die session dadurch als verifiziert markiert wurde
     */
    public function verifyViaTrustedDevice()
    {
        if (!$this->isTrustedDevice()) {
            return false;
        }

        rex_set_session('otp_verified', true);

        return true;
    }

    /**
     * Prueft, ob fuer den "eingeloggt bleiben"-cookie der aktuellen session ein
     * gueltiger (nicht abgelaufener) trusted-device-eintrag existiert.
     *
     * @return bool
     */
    public function isTrustedDevice()
    {
        $days = $this->getTrustedDeviceDays();
        if (0 === $days) {
            return false;
        }

        $cookieKey = $this->getCurrentCookieKey();
        if (null === $cookieKey) {
            return false;
        }

        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT id FROM ' . rex::getTable('2factor_auth_trusted_device')
            . ' WHERE cookie_key_hash = ? AND user_id = ? AND verified_at >= ? LIMIT 1',
            [
                hash('sha256', $cookieKey),
                $this->getUser()->getId(),
                rex_sql::datetime(time() - $days * 86400),
            ],
        );

        return $sql->getRows() > 0;
    }

    /**
     * Haelt fest, dass der zweite faktor fuer den "eingeloggt bleiben"-cookie der aktuellen
     * session erbracht wurde. Bei sessions ohne "eingeloggt bleiben" (kein cookie) passiert
     * nichts, sodass der zweite faktor dort wie gehabt pro session abgefragt wird.
     *
     * @return void
     */
    public function rememberTrustedDevice()
    {
        if (0 === $this->getTrustedDeviceDays()) {
            return;
        }

        $cookieKey = $this->getCurrentCookieKey();
        if (null === $cookieKey) {
            return;
        }

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('2factor_auth_trusted_device'));
        $sql->setValue('cookie_key_hash', hash('sha256', $cookieKey));
        $sql->setValue('user_id', $this->getUser()->getId());
        $sql->setValue('verified_at', rex_sql::datetime());
        $sql->insertOrUpdate();

        $this->purgeExpiredTrustedDevices();
    }

    /**
     * Entfernt abgelaufene trusted-device-eintraege.
     *
     * @return void
     */
    private function purgeExpiredTrustedDevices()
    {
        $days = $this->getTrustedDeviceDays();
        if (0 === $days) {
            return;
        }

        $sql = rex_sql::factory();
        $sql->setQuery(
            'DELETE FROM ' . rex::getTable('2factor_auth_trusted_device') . ' WHERE verified_at < ?',
            [rex_sql::datetime(time() - $days * 86400)],
        );
    }

    /**
     * Liefert den "eingeloggt bleiben"-cookie-key der aktuellen session, sofern vorhanden.
     * Nur stay-logged-in-sessions haben einen cookie_key hinterlegt.
     *
     * @return string|null
     */
    private function getCurrentCookieKey()
    {
        $sessionId = session_id();
        if (false === $sessionId || '' === $sessionId) {
            return null;
        }

        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT cookie_key FROM ' . rex::getTable('user_session') . ' WHERE session_id = ? LIMIT 1',
            [$sessionId],
        );

        if (0 === $sql->getRows()) {
            return null;
        }

        $cookieKey = $sql->getValue('cookie_key');

        return null === $cookieKey ? null : (string) $cookieKey;
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
