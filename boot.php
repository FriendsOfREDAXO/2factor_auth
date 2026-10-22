<?php

use FriendsOfREDAXO\TwoFactorAuth\one_time_password;

$addon = rex_addon::get('2factor_auth');

if (rex::isBackend() && null !== rex::getUser()) {
    if ('2factor_auth' === rex_be_controller::getCurrentPagePart(1)) {
        rex_view::addJsFile($addon->getAssetsUrl('qrious.min.js'));
        rex_view::addJsFile($addon->getAssetsUrl('clipboard-copy-element.js'));
    }

    $otp = one_time_password::getInstance();

    // ein passkey (WebAuthn) ist bereits ein phishing-resistenter faktor und ersetzt
    // den zweiten faktor. Bei passkey-anmeldung keine 2fa erzwingen oder abfragen.
    if ($otp->isPasskeyLogin()) {
        return;
    }

    // bei einer impersonation zaehlt immer der real angemeldete benutzer
    $realUser = rex::getImpersonator() ?? rex::requireUser();

    $currentPage = rex_be_controller::getCurrentPage();

    /**
     * Bricht den request ab, solange die 2fa nicht abgeschlossen ist.
     *
     * Der abbruch erfolgt erst bei PACKAGES_INCLUDED (EARLY) und nicht direkt im boot:
     * beim login-request erneuert der core die session-id, bevor die addons geladen sind,
     * und meldet das ueber SESSION_REGENERATED erst bei PACKAGES_INCLUDED. Ein exit im
     * boot verhindert diese meldung, dann behaelt z.b. ycom die alte session-id und die
     * frontend-anmeldung geht verloren. Der core registriert seinen listener vor dem boot,
     * er laeuft deshalb vor diesem.
     *
     * Bis dahin verwirft ein ausgabepuffer alles, was andere addons in ihrem boot ausgeben
     * (z.b. debug mit `load: early`) - auch dann, wenn sie den request selbst beenden. So
     * kommt weiterhin keine backend-ausgabe vor dem abgeschlossenen zweiten faktor durch.
     *
     * @param list<string> $allowedPages
     * @return void
     */
    $blockRequest = static function (array $allowedPages, string $target) use ($currentPage) {
        // api-funktionen werden unabhaengig von der aufgerufenen seite ausgefuehrt
        $isApiCall = '' !== rex_request(rex_api_function::REQ_CALL_PARAM, 'string', '');

        if (!$isApiCall && in_array($currentPage, $allowedPages, true)) {
            return;
        }

        ob_start(static function (): string {
            return '';
        });

        rex_extension::register('PACKAGES_INCLUDED', static function () use ($isApiCall, $target) {
            rex_response::cleanOutputBuffers();

            if ($isApiCall) {
                // die antwort wird direkt gesendet statt eine exception zu werfen: der globale
                // exception-handler wuerde diesen erwarteten, unkritischen fall sonst als fehler
                // ins system-log schreiben (z.b. der session-keep-alive-ping waehrend der
                // 2fa-eingabe, siehe issue #110).
                rex_response::setStatus(rex_response::HTTP_UNAUTHORIZED);
                rex_response::sendContent(
                    'the api function can only be called after a completed two-factor authentication!',
                    'text/plain; charset=utf-8',
                );
                exit;
            }

            rex_response::sendRedirect(rex_url::backendPage($target));
        }, rex_extension::EARLY);
    };

    // den benutzer auf das setup leiten, weil erzwungen aber noch nicht durchgefuehrt
    if (!$otp->isEnabled()) {
        if (one_time_password::ENFORCED_ALL === $otp->isEnforced()
            || one_time_password::ENFORCED_ADMINS === $otp->isEnforced() && $realUser->isAdmin()) {
            // das eigene profil bleibt erreichbar, dort wird die fuer die
            // e-mail-methode benoetigte adresse hinterlegt
            $blockRequest(['2factor_auth/setup', 'profile'], '2factor_auth/setup');
            return;
        }
    }

    // den benutzer zur einmal passwort eingabe leiten, weil one-time-passwort aktiv
    // und bisher fuer die session noch nicht eingegeben
    if ($otp->isEnabled()) {
        // auf einem "eingeloggt bleiben"-geraet, das den zweiten faktor bereits innerhalb
        // des konfigurierten zeitraums erbracht hat, die session ohne erneute abfrage
        // verifizieren (trusted device)
        if (!$otp->isVerified()) {
            $otp->verifyViaTrustedDevice();
        }

        if (!$otp->isVerified()) {
            // die verify-page laeuft ueber die profile-page, damit ein ggf. erzwungener
            // passwortwechsel erst nach der verifizierung greift
            $blockRequest(['profile'], 'profile');

            rex_extension::register('PAGES_PREPARED', static function (rex_extension_point $ep) {
                $profilePage = rex_be_controller::getPageObject('profile');
                if (!$profilePage) {
                    return;
                }
                $profilePage->setPath(rex_path::addon('2factor_auth', 'pages/verify.php'));
                $profilePage->setHasNavigation(false);
                $profilePage->setPjax(false);
                rex_extension::register('PAGE_BODY_ATTR', static function (rex_extension_point $ep) {
                    $attributes = $ep->getSubject();
                    /** add rex-page-login id */
                    $attributes['id'] = ['rex-page-login'];
                    $ep->setSubject($attributes);
                });
            });
        }
    }
}
