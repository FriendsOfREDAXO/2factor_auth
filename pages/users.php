<?php

use FriendsOfREDAXO\TwoFactorAuth\one_time_password_config;

/** @var rex_addon $this */

$csrfToken = rex_csrf_token::factory('2factor_auth_users');

if ('deactivate' == rex_get('func', 'string') && $userid = rex_get('userid', 'int')) {
    if (!$csrfToken->isValid()) {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    } else {
        try {
            $user = rex_user::get($userid);
            if (null === $user) {
                throw new rex_exception('User with id ' . $userid . ' does not exist');
            }

            $config = one_time_password_config::forUser($user);
            $config->disable();

            echo rex_view::success('User ' . rex_escape($user->getLogin()) . ' deactivated');
        } catch (Exception $e) {
            echo rex_view::error($e->getMessage());
        }
    }
}

$users = rex_sql::factory();
$users
    ->setTable(rex::getTable('user'))
    ->select();

$content = '<table class="table">';

$content .= '<thead>';
$content .= '<tr>';
$content .= '<th>id</th>';
$content .= '<th>login</th>';
$content .= '<th>status</th>';
$content .= '<th>method</th>';
$content .= '<th>tries</th>';
$content .= '<th>last_try</th>';
$content .= '<th>actions</th>';
$content .= '</tr>';
$content .= '</thead>';

$content .= '<tbody>';

$trs = [];
foreach ($users as $user) {
    $user = rex_user::fromSql($user);
    $config = one_time_password_config::forUser($user);
    $trs[] = '<tr>
                <td data-title="id">' . rex_escape($user->getId()) . '</td>
                <td data-title="login">' . rex_escape($user->getLogin()) . '</td>
                <td data-title="enabled">' . rex_escape($config->enabled ? 'on' : 'off') . '</td>
                <td data-title="method">' . rex_escape($config->method) . '</td>
                <td data-title="tries">' . rex_escape($user->getValue('one_time_password_tries')) . '</td>
                <td data-title="last try">' . rex_escape($user->getValue('one_time_password_lasttry')) . '</td>
                <td data-title="action"><a href="' . rex_url::currentBackendPage(['func' => 'deactivate', 'userid' => $user->getId()] + $csrfToken->getUrlParams()) . '">deactivate</a></td>
                </tr>';
}

$content .= implode('', $trs);

$content .= '</tbody>';
$content .= '</table>';

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('users'), false);
$fragment->setVar('content', $content, false);
echo $fragment->parse('core/page/section.php');
