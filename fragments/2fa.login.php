<form id="rex-form-login" method="post" autocomplete="off">
    <input type="hidden" name="page" value="2factor_auth_verify"/>
    <?php echo $this->csrfToken->getHiddenField(); ?>
    <section class="rex-page-section">
        <div class="panel panel-default">
            <div class="panel-body">
                <?php
                $fragment = new rex_fragment();
                echo $fragment->parse('core/login_branding.php');
                ?>

                <?php if ('' !== $this->message) : ?>
                    <div class="rex-js-login-message">
                        <div class="alert <?= $this->error ? 'alert-danger' : 'alert-success' ?>">
                            <?= $this->message?>
                        </div>
                    </div>
                <?php endif; ?>

                <fieldset>
                    <input type="hidden" name="javascript" value="1" id="javascript">
                    <dl class="rex-form-group form-group rex-form-group-vertical">
                        <dt><label for="rex_login_otp"><?= rex_i18n::msg('2factor_auth_2fa_one_time_password') ?></label></dt>
                        <dd>
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="rex-icon rex-icon-user-secret"></i>
                                </span>
                                <input class="form-control" type="text" value="" id="rex_login_otp" name="rex_login_otp" autofocus>
                            </div>
                        </dd>
                    </dl>
                </fieldset>
            </div>
            <footer class="panel-footer">
                <div class="rex-form-panel-footer">
                    <div class="btn-toolbar">
                        <button class="btn btn-primary" type="submit">
                            <i class="rex-icon rex-icon-sign-in"></i>
                            <?= rex_i18n::msg('login') ?>
                        </button>
                        <a class="btn btn-link"
                           href="<?php echo rex_url::backendController(['rex_logout' => 1] + rex_csrf_token::factory('backend_logout')->getUrlParams()); ?>">
                            <i class="rex-icon rex-icon-sign-out"></i> <?php echo rex_i18n::msg('logout'); ?>
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    </section>
</form>

<style>
    #rex-js-page-main .rex-global-footer {
        display: none;
    }
</style>

<?php
$fragment = new rex_fragment();
echo $fragment->parse('core/login_background.php');
?>
