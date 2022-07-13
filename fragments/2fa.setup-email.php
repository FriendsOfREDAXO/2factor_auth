<?php
?>

<?php if ($this->message) : ?>
    <?= $this->message ?>
<?php endif; ?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">2. <?= $this->addon->i18n('2fa_verify_headline') ?></h3>
    </div>
    <div class="panel-body">
        <form method="post" action="./<?= rex_url::currentBackendPage() ?>">
            <?php echo $this->csrfToken->getHiddenField(); ?>
            <input type="hidden" name="func" value="verify-email" />

            <div class="form-group">
                <div class="input-group">
                    Passwort versand an E-Mail aus dem Userprofil: <?php echo rex_escape(rex::requireUser()->getEmail()); ?>
                </div>
                <br />
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

