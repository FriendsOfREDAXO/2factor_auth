<?php

use FriendsOfREDAXO\TwoFactorAuth\one_time_password;

$addon = rex_addon::get('2factor_auth');

if (rex::isBackend() && null !== rex::getUser()) {
    if ('2factor_auth' === rex_be_controller::getCurrentPagePart(1)) {
        rex_view::addJsFile($addon->getAssetsUrl('qrious.min.js'));
        rex_view::addJsFile($addon->getAssetsUrl('clipboard-copy-element.js'));
    }

    $otp = one_time_password::getInstance();

    // bei einer impersonation zaehlt immer der real angemeldete benutzer
    $realUser = rex::getImpersonator() ?? rex::requireUser();

    $currentPage = rex_be_controller::getCurrentPage();

    /**
     * Bricht den request ab, solange die 2fa nicht abgeschlossen ist.
     *
     * Der abbruch erfolgt bewusst hier im boot und nicht erst ueber den page-controller:
     * andere addons mit `load: early` (z.b. debug) liefern ihre ausgabe bereits im
     * eigenen boot aus und wuerden eine reine umleitung der backend-page umgehen.
     *
     * @param list<string> $allowedPages
     * @return void
     */
    $blockRequest = static function (array $allowedPages, string $target) use ($currentPage) {
        // api-funktionen werden unabhaengig von der aufgerufenen seite ausgefuehrt
        if ('' !== rex_request(rex_api_function::REQ_CALL_PARAM, 'string', '')) {
            throw new rex_http_exception(
                new rex_api_exception('the api function can only be called after a completed two-factor authentication!'),
                rex_response::HTTP_UNAUTHORIZED,
            );
        }

        if (!in_array($currentPage, $allowedPages, true)) {
            rex_response::sendRedirect(rex_url::backendPage($target));
        }
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
