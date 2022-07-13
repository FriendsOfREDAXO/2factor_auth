<?php

namespace rex_2fa;

use rex_system_setting;
use rex_i18n;
use rex_form_select_element;

class enforce_system_setting extends rex_system_setting
{
    public const ENABLED = 1;
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
        $select->addOption(rex_i18n::msg('2factor_auth_2fa_enforced_yes'), self::ENABLED);
        $select->addOption(rex_i18n::msg('2factor_auth_2fa_enforced_no'), self::DISABLED);

        $otp = one_time_password::getInstance();
        $select->setSelected($otp->isEnforced() ? self::ENABLED : self::DISABLED);

        return $field;
    }

    public function setValue($value)
    {
        $value = (int) $value;

        $opt = one_time_password::getInstance();
        $opt->enforce($value === self::ENABLED);

        return true;
    }
}
