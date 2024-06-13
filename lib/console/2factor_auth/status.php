<?php

use FriendsOfREDAXO\TwoFactorAuth\one_time_password;
use FriendsOfREDAXO\TwoFactorAuth\one_time_password_config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package redaxo\2factor_auth
 *
 * @internal
 */
class rex_command_2factor_auth_status extends rex_console_command
{
    protected function configure(): void
    {
        $this
            ->setDescription('List of users with status info')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getStyle($input, $output);

        $opt = one_time_password::getInstance();
        $status = '-';
        switch ($opt->isEnforced()) {
            case one_time_password::ENFORCED_ALL:
                $status = 'all';
                break;
            case one_time_password::ENFORCED_ADMINS:
                $status = 'admins';
                break;
            case one_time_password::ENFORCED_DISABLED:
                $status = 'nobody';
                break;
        }

        $io->text('2Factor-Auth is mandatory for: <comment>' . $status . '</comment>');
        $io->text('');

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
            ];
        }

        $io->table([
            'id',
            'login',
            'status',
            'method',
        ], $userRows);

        return 0;
    }
}
