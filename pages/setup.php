<?php

echo rex_view::title(rex_i18n::msg('2factor_auth_setup'), '');

$uri = rex_one_time_password::getInstance()->getProvisioningUri();

?>
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