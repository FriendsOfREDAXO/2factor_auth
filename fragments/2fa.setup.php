<?php
?>

<?php if ($this->message) : ?>
    <?= $this->message ?>
<?php endif; ?>

<?php if (!$this->success) : ?>
    <div class="row">
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">1. <?= $this->addon->i18n('2fa_setup_scan') ?></h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <canvas id="qr-code"></canvas>
                    </div>
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo $this->uri; ?>" id="2fa-uri" readonly>
                            <div class="input-group-btn">
                                <clipboard-copy for="2fa-uri" class="btn btn-primary">
                                    <i class="rex-icon fa-copy"></i> <?= $this->addon->i18n('copy') ?>
                                </clipboard-copy>
                            </div>
                        </div>
                    </div>
                    <div id="notice" hidden class="alert alert-success">Copied to clipboard</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">2. <?= $this->addon->i18n('2fa_verify_headline') ?></h3>
                </div>
                <div class="panel-body">
                    <form method="post" action="./<?= rex_url::currentBackendPage() ?>">
                        <?php echo $this->csrfToken->getHiddenField(); ?>
                        <input type="hidden" name="func" value="verify" />
                        <div class="form-group">
                            <div class="input-group">
                                <input type="text" class="form-control" name="rex_login_otp" id="rex_login_otp">
                                <div class="input-group-btn">
                                    <button type="submit" name="nbla" class="btn btn-primary">
                                        <i class="rex-icon fa-save"></i> <?= $this->addon->i18n('2fa_verify_action') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    clipboard-copy {
        border: 2px solid black;
        cursor: default;
    }
</style>

<script>
    new QRious({
        element: document.getElementById("qr-code"),
        value: document.getElementById("2fa-uri").value,
        size: 300
    });

    document.addEventListener('copy', function () {
        const notice = document.getElementById('notice')
        notice.hidden = false
        setTimeout(function () {
            notice.hidden = true
        }, 1000)
    })
</script>
