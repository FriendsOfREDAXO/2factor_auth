<?php

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package redaxo\2factor_auth
 *
 * @internal
 */
class rex_command_2factor_auth_user extends rex_console_command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Deaktivates a 2factor_auth for a user')
            ->addArgument('user', InputArgument::REQUIRED, 'Username')
            ->addOption('disable', 'disable', InputOption::VALUE_OPTIONAL, 'Disable', 'none')
            ->addOption('enable', 'enable', InputOption::VALUE_OPTIONAL, 'Enable', 'none')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getStyle($input, $output);

        $username = $input->getArgument('user');

        $user = rex_sql::factory();
        $user
            ->setTable(rex::getTable('user'))
            ->setWhere(['login' => $username])
            ->select();

        if (1 != $user->getRows()) {
            throw new InvalidArgumentException(sprintf('User "%s" does not exist.', $username));
        }

        $user = rex_user::fromSql($user);
        $config = \rex_2fa\one_time_password_config::forUser($user);

        $io->info('User found: '.$user->getLogin()."\n".'Current status: '.$config->method);

        $enable = $input->getOption('enable');
        $disable = $input->getOption('disable');

        if ('none' == $enable && 'none' == $disable) {
            $io->warning('Please decide: (--enable) or (--disable) for disabling 2factor_auth');
            return 0;
        }

        if ('none' != $enable) {
            $config->enable();
            $io->success('2factor_auth for User `'.$user->getLogin().'` has been enabled');
        }

        if ('none' != $disable) {
            $config->disable();
            $io->success('2factor_auth for User `'.$user->getLogin().'` has been disabled');
        }

        return 0;
    }
}
