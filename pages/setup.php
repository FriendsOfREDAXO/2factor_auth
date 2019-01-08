<?php

echo rex_view::title(rex_i18n::msg('2factor_auth_setup'), '');

$csrfToken = rex_csrf_token::factory('2factor_auth_setup');
$func = rex_request('func', 'string');

$otp = rex_one_time_password::getInstance();

if ($func && !$csrfToken->isValid()) {
    echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    $func = '';
}

if ($func === 'disable') {
    $config = rex_one_time_password_config::loadFromDb();
    $config->disable();

    $func = '';
}

if ($otp->enabled()) {
    echo rex_view::info('2 Faktor Authentifizierung ist aktiviert');

    echo '<p><a class="btn btn-delete" href="' . rex_url::currentBackendPage(['func' => 'disable'] + $csrfToken->getUrlParams()) . '">' . rex_i18n::msg('2factor_auth_disable') . '</a></p>';
} else {
    echo rex_view::info('2 Faktor Authentifizierung ist deaktiviert');

    if (empty($func)) {
        echo '<p><a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'setup'] + $csrfToken->getUrlParams()) . '">' . rex_i18n::msg('2factor_auth_setup') . '</a></p>';
    } elseif ($func === 'setup') {
        $config = rex_one_time_password_config::loadFromDb();
        $uri = $config->provisioningUri; ?>
        <input type="hidden" value="<?php echo $uri; ?>" id="2fa-uri">

        <canvas id="qr-code"></canvas>
        <script src="<?php echo $this->getAssetsUrl('qrious.min.js'); ?>"></script>
        <script>
            new QRious({
                element: document.getElementById("qr-code"),
                value: document.getElementById("2fa-uri").value,
                size: 300
            });
        </script>

        <?php

        echo '<p><a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'verify'] + $csrfToken->getUrlParams()) . '">' . rex_i18n::msg('2factor_auth_setup_verify') . '</a></p>';
    } elseif ($func === 'verify') {
        $otp = rex_post('rex_login_otp', 'string', null);

        $message = '';
        if ($otp !== null) {
            if (rex_one_time_password::getInstance()->verify($otp)) {
                $message = rex_view::success('Passt');

                $config = rex_one_time_password_config::loadFromDb();
                $config->enable();
            } else {
                $message = rex_view::warning('Falsches one-time-password, bitte erneut versuchen');
            }
        }

        echo $message; ?>
        <form method="post">
            <?php echo $csrfToken->getHiddenField(); ?>
            <input type="hidden" name="page" value="2factor_auth_setup"/>
            <input type="hidden" name="func" value="verify"/>
            <input type="text" name="rex_login_otp"/>
            <input type="submit"/>
        </form>
        <?php
    } else {
        throw new Exception('unknown state');
    }
}
