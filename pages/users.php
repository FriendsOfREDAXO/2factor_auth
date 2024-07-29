<?php

use FriendsOfREDAXO\TwoFactorAuth\one_time_password_config;

$users = rex_sql::factory();
$users
    ->setTable(rex::getTable('user'))
    ->select();

$userRows = [];
foreach ($users as $user) {
    $user = rex_user::fromSql($user);
    $config = one_time_password_config::forUser($user);
    $userRows[] = [
        $user->getId(),
        $user->getLogin(),
        $config->enabled ? 'on' : 'off',
        $config->method,
        $user->getValue('one_time_password_tries'),
        $user->getValue('one_time_password_lasttry'),
    ];
}

$content = '<table class="table">';

$content .= '<thead>';
$content .= '<tr>';
$content .= '<th>id</th>';
$content .= '<th>login</th>';
$content .= '<th>status</th>';
$content .= '<th>method</th>';
$content .= '<th>tries</th>';
$content .= '<th>last_try</th>';
$content .= '</tr>';
$content .= '</thead>';

$content .= '<tbody>';
foreach ($userRows as $row) {
    $content .= '<tr>';
    $content .= '<td data-title="id">' . rex_escape($row[0]) . '</td>';
    $content .= '<td data-title="login">' . rex_escape($row[1]) . '</td>';
    $content .= '<td data-title="enabled">' . rex_escape($row[2]) . '</td>';
    $content .= '<td data-title="method">' . rex_escape($row[3]) . '</td>';
    $content .= '<td data-title="method">' . rex_escape($row[4]) . '</td>';
    $content .= '<td data-title="method">' . rex_escape($row[5]) . '</td>';
    $content .= '</tr>';
}
$content .= '</tbody>';
$content .= '</table>';

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('users'), false);
$fragment->setVar('content', $content, false);
echo $fragment->parse('core/page/section.php');
