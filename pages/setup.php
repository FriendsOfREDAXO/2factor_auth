<?php

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
    echo rex_view::info($this->i18n('2fa_active'));

    echo '<p><a class="btn btn-delete" href="' . rex_url::currentBackendPage(['func' => 'disable'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_disable') . '</a></p>';
} else {
    echo rex_view::info($this->i18n('2fa_inactive'));

    if (empty($func)) {
        echo '<p><a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'setup'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_setup') . '</a></p>';
    } elseif ($func === 'setup') {
        $config = rex_one_time_password_config::loadFromDb();
        $uri = $config->provisioningUri; ?>
<input type="text" value="<?php echo $uri; ?>" id="2fa-uri" readonly>
<clipboard-copy for="2fa-uri" class="">
    Copy
</clipboard-copy>
<div id="notice" hidden>Copied to clipboard</div>

<br /><br />

<canvas id="qr-code"></canvas>

<script
    src="<?php echo $this->getAssetsUrl('qrious.min.js'); ?>">
</script>
<script>
    new QRious({
        element: document.getElementById("qr-code"),
        value: document.getElementById("2fa-uri").value,
        size: 300
    });
</script>


<style>
    clipboard-copy {
        border: 2px solid black;
        cursor: default;
    }
</style>
<script
    src="<?php echo $this->getAssetsUrl('clipboard-copy-element.js'); ?>">
</script>
<script>
    document.addEventListener('copy', function() {
        const notice = document.getElementById('notice')
        notice.hidden = false
        setTimeout(function() {
            notice.hidden = true
        }, 1000)
    })
</script>

<?php

        echo '<p><a class="btn btn-setup" href="' . rex_url::currentBackendPage(['func' => 'verify'] + $csrfToken->getUrlParams()) . '">' . $this->i18n('2fa_setup_verify') . '</a></p>';
    } elseif ($func === 'verify') {
        $otp = rex_post('rex_login_otp', 'string', null);

        $message = '';
        if ($otp !== null) {
            if (rex_one_time_password::getInstance()->verify($otp)) {
                $message = rex_view::success($this->i18n('2fa_setup_successfull'));

                $config = rex_one_time_password_config::loadFromDb();
                $config->enable();
            } else {
                $message = rex_view::warning($this->i18n('2fa_wrong_opt'));
            }
        }

        echo $message; ?>
<form method="post">
    <p><?php echo $this->i18n('2fa_verify_headline'); ?>
    </p>
    <?php echo $csrfToken->getHiddenField(); ?>
    <input type="hidden" name="page" value="2factor_auth_setup" />
    <input type="hidden" name="func" value="verify" />
    <input type="text" name="rex_login_otp" />
    <input type="submit"
        value="<?php echo $this->i18n('2fa_verify_action'); ?>" />
</form>
<?php
    } else {
        throw new Exception('unknown state');
    }
}
