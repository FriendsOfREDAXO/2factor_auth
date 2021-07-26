<?php

    $config = rex_one_time_password_config::loadFromDb();
        $uri = $config->provisioningUri; ?>

<div class="row">
    <div class="col-md-2">
        <canvas id="qr-code" class="img-rounded img-responsive"></canvas>
    </div>
    <div class="col-md-10">
        <p><?= rex_view::info($this->i18n('2factor_auth_2fa_instruction')); ?>
        </p>
        <div class="input-group">
            <input class="form-control" type="text"
                value="<?php echo $uri; ?>" id="2fa-uri" readonly>
            <clipboard-copy for="2fa-uri" class="btn btn-primary">
                <?= $this->i18n('2factor_auth_2fa_copy') ?>
            </clipboard-copy>
        </div>

        <div id="notice" hidden>✅ <?= rex_view::warning($this->i18n('2factor_auth_2fa_copied')); ?>
        </div>
    </div>
</div>

<script>
    new QRious({
        element: document.getElementById("qr-code"),
        value: document.getElementById("2fa-uri").value,
        size: 300
    });

    document.addEventListener('copy', function() {
        const notice = document.getElementById('notice')
        notice.hidden = false
        setTimeout(function() {
            notice.hidden = true
        }, 1000)
    })
</script>
<style>
    clipboard-copy {
        border: 2px solid black;
        cursor: default;
    }
</style>