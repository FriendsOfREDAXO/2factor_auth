<?php


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
?>

<form method="post"
    action="<?= rex_url::currentBackendPage(['func' => 'verify'] + $this->getVar("csrfToken")->getUrlParams()) ?>">
    <p><?= $this->i18n('2fa_verify_headline'); ?>
    </p>
    <?= $this->getVar("csrfToken")->getHiddenField(); ?>
    <input class="form-control" type="hidden" name="page" value="2factor_auth_setup" />
    <input type="hidden" name="func" value="verify" />
    <input class="form-control" type="text" name="rex_login_otp" />
    <input class="btn btn-primary" type="submit"
        value="<?= $this->i18n('2fa_verify_action'); ?>" />
</form>

<?php echo $message;
