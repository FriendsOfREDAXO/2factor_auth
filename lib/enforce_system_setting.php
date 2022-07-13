<?php

namespace rex_2fa;

use rex_system_setting;
use rex_i18n;
use rex_form_select_element;

/**
 * @internal
 */
class enforce_system_setting extends rex_system_setting
{
    public const ENABLED_ALL = 1;
    public const ENABLED_ADMINS = 2;
    public const DISABLED = -1;

    public function getKey()
    {
        return '2fa_enforce';
    }

    public function getField()
    {
        $field = new rex_form_select_element();
        $field->setAttribute('class', 'form-control selectpicker');
        $field->setLabel(rex_i18n::msg('2factor_auth_2fa_enforced'));

        $select = $field->getSelect();
        $select->addOption(rex_i18n::msg('2factor_auth_2fa_enforced_yes_all'), self::ENABLED_ALL);
        $select->addOption(rex_i18n::msg('2factor_auth_2fa_enforced_yes_admins'), self::ENABLED_ADMINS);
        $select->addOption(rex_i18n::msg('2factor_auth_2fa_enforced_no'), self::DISABLED);

        $otp = one_time_password::getInstance();

        switch($otp->isEnforced()) {
            case one_time_password::ENFORCED_ALL:
                $select->setSelected(self::ENABLED_ALL);
                break;
            case one_time_password::ENFORCED_ADMINS:
                $select->setSelected(self::ENABLED_ADMINS);
                break;
            case one_time_password::ENFORCED_DISABLED:
                $select->setSelected(self::DISABLED);
                break;
        }

        return $field;
    }

    public function setValue($value)
    {
        $value = (int) $value;

        $opt = one_time_password::getInstance();

        switch($value) {
            case self::ENABLED_ALL:
                $opt->enforce(one_time_password::ENFORCED_ALL);
                break;
            case self::ENABLED_ADMINS:
                $opt->enforce(one_time_password::ENFORCED_ADMINS);
                break;
            case self::DISABLED:
                $opt->enforce(one_time_password::ENFORCED_DISABLED);
                break;
            default:
                throw new \InvalidArgumentException('Invalid value '. $value .' for 2fa enforce');
        }

        return true;
    }
}
