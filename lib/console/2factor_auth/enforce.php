<?php

use rex_2fa\one_time_password;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package redaxo\2factor_auth
 *
 * @internal
 */
class rex_command_2factor_auth_enforce extends rex_console_command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Enforce/Disable a 2factor_auth for users/admins or admins')
            ->addOption('all', 'all', InputOption::VALUE_OPTIONAL, 'All', 'none')
            ->addOption('admins', 'admins', InputOption::VALUE_OPTIONAL, 'Admins only', 'none')
            ->addOption('disable', 'disable', InputOption::VALUE_OPTIONAL, 'Disable', 'none')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getStyle($input, $output);

        $all = $input->getOption('all');
        $admins = $input->getOption('admins');
        $disable = $input->getOption('disable');

        $opt = one_time_password::getInstance();
        $status = '';
        switch ($opt->isEnforced()) {
            case one_time_password::ENFORCED_ALL:
                $status = 'all';
                break;
            case one_time_password::ENFORCED_ADMINS:
                $status = 'admins';
                break;
            case one_time_password::ENFORCED_DISABLED:
                $status = 'disabled';
                break;
        }
        if ('none' == $all && 'none' == $admins && 'none' == $disable) {
            $io->info('Please decide: (--all) for all, (--admins) for admins or (--disable) for disabling 2factor_auth enforcement.
Current Status: '.$status);
            return 0;
        }

        if ('none' != $all) {
            $value = one_time_password::ENFORCED_ALL;
            $io->success('2factor_auth is now enforced for all users');
        } elseif ('none' != $admins) {
            $value = one_time_password::ENFORCED_ADMINS;
            $io->success('2factor_auth is now enforced for admins only');
        } else {
            $value = one_time_password::ENFORCED_DISABLED;
            $io->success('Enforcement of 2factor_auth for all has been disabled');
        }
        $opt->enforce($value);

        return 0;
    }
}
