<?php

/** @var rex_addon $this */

$table = rex_yform_manager_table::get('rex_ycom_user');

if ('update' == rex_request('func', 'string')) {
    $this->setConfig('enforce', rex_request('2factor_auth_enforce', 'string'));
    $this->setConfig('option', rex_request('2factor_auth_option', 'string'));
    $this->setConfig('email_period', rex_request('2factor_auth_email_period', 'int', 300));
    echo rex_view::success($this->i18n('2factor_auth_updated'));
}

$selectEnforce = new rex_select();
$selectEnforce->setId('2factor_auth_enforce');
$selectEnforce->setName('2factor_auth_enforce');
$selectEnforce->setAttribute('class', 'form-control selectpicker');
$selectEnforce->setSelected($this->getConfig('enforce'));

$selectEnforce->addOption($this->i18n('2factor_auth_enforce_'.FriendsOfREDAXO\TwoFactorAuth\one_time_password::ENFORCED_ALL), FriendsOfREDAXO\TwoFactorAuth\one_time_password::ENFORCED_ALL);
$selectEnforce->addOption($this->i18n('2factor_auth_enforce_'.FriendsOfREDAXO\TwoFactorAuth\one_time_password::ENFORCED_ADMINS), FriendsOfREDAXO\TwoFactorAuth\one_time_password::ENFORCED_ADMINS);
$selectEnforce->addOption($this->i18n('2factor_auth_enforce_'.FriendsOfREDAXO\TwoFactorAuth\one_time_password::ENFORCED_DISABLED), FriendsOfREDAXO\TwoFactorAuth\one_time_password::ENFORCED_DISABLED);

$selectOption = new rex_select();
$selectOption->setId('2factor_auth_option');
$selectOption->setName('2factor_auth_option');
$selectOption->setAttribute('class', 'form-control selectpicker');
$selectOption->setSelected($this->getConfig('option'));

$selectOption->addOption($this->i18n('2factor_auth_option_'.FriendsOfREDAXO\TwoFactorAuth\one_time_password::OPTION_ALL), FriendsOfREDAXO\TwoFactorAuth\one_time_password::OPTION_ALL);
$selectOption->addOption($this->i18n('2factor_auth_option_'.FriendsOfREDAXO\TwoFactorAuth\one_time_password::OPTION_TOTP), FriendsOfREDAXO\TwoFactorAuth\one_time_password::OPTION_TOTP);
$selectOption->addOption($this->i18n('2factor_auth_option_'.FriendsOfREDAXO\TwoFactorAuth\one_time_password::OPTION_EMAIL), FriendsOfREDAXO\TwoFactorAuth\one_time_password::OPTION_EMAIL);

$selectEmailPeriod = new rex_select();
$selectEmailPeriod->setId('2factor_auth_email_period');
$selectEmailPeriod->setName('2factor_auth_email_period');
$selectEmailPeriod->setAttribute('class', 'form-control selectpicker');
$selectEmailPeriod->setSelected($this->getConfig('email_period'));

$selectEmailPeriod->addOption('5 '.$this->i18n('minutes'), 300);
$selectEmailPeriod->addOption('10 '.$this->i18n('minutes'), 600);
$selectEmailPeriod->addOption('15 '.$this->i18n('minutes'), 900);
$selectEmailPeriod->addOption('30 '.$this->i18n('minutes'), 1800);

$content = '
<form action="index.php" method="post" id="ycom_auth_settings">
    <input type="hidden" name="page" value="2factor_auth/settings" />
    <input type="hidden" name="func" value="update" />

    <fieldset>
            <div class="row abstand">
                <div class="col-xs-12 col-sm-6">
                    <label for="2factor_auth_enforce">' . $this->i18n('2factor_auth_enforce') . '</label>
                </div>
                <div class="col-xs-12 col-sm-6">
                '.$selectEnforce->get().'
                </div>
            </div>

            <div class="row abstand">
                <div class="col-xs-12 col-sm-6">
                    <label for="2factor_auth_options">' . $this->i18n('2factor_auth_options') . '</label>
                </div>
                <div class="col-xs-12 col-sm-6">
                '.$selectOption->get().'
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-6">
                    <label for="2factor_auth_email_period">' . $this->i18n('2factor_auth_email_period') . '</label>
                </div>
                <div class="col-xs-12 col-sm-6">
                '.$selectEmailPeriod->get().'
            </div>

    </fieldset>

	<div class="row">
		<div class="col-xs-12 col-sm-6 col-sm-push-6">
			<button class="btn btn-save right" type="submit" name="config-submit" value="1" title="'.$this->i18n('2factor_auth_config_save').'">'.$this->i18n('ycom_auth_config_save').'</button>
		</div>
	</div>

	</form>

  ';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', $this->i18n('2factor_auth_settings'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
