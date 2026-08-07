<?php

use FriendsOfREDAXO\TwoFactorAuth\one_time_password_config;
use OTPHP\Factory;
use OTPHP\TOTPInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Gibt den aktuell gueltigen code eines benutzers aus.
 *
 * Gedacht fuer entwicklung und support: der code laesst sich damit ohne
 * authenticator-app bzw. ohne zustellbare e-mail ermitteln. Zugriff auf die
 * konsole bedeutet ohnehin zugriff auf die datenbank und damit auf das secret.
 *
 * @package redaxo\2factor_auth
 *
 * @internal
 */
class rex_command_2factor_auth_code extends rex_console_command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Shows the currently valid one-time password of a user')
            ->addArgument('user', InputArgument::REQUIRED, 'Username')
            ->addOption('secret', null, InputOption::VALUE_NONE, 'Additionally show the secret and the provisioning uri')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getStyle($input, $output);

        $username = $input->getArgument('user');

        $userSql = rex_sql::factory();
        $userSql
            ->setTable(rex::getTable('user'))
            ->setWhere(['login' => $username])
            ->select();

        if (1 != $userSql->getRows()) {
            throw new InvalidArgumentException(sprintf('User "%s" does not exist.', $username));
        }

        $user = rex_user::fromSql($userSql);
        $config = one_time_password_config::forUser($user);

        if (null === $config->provisioningUri) {
            $io->warning(sprintf('No 2factor_auth secret has been created for user "%s" yet.', $username));
            return 1;
        }

        $otp = Factory::loadFromProvisioningUri(str_replace('&amp;', '&', $config->provisioningUri));
        if (!$otp instanceof TOTPInterface) {
            $io->error('Only time based one-time passwords are supported.');
            return 1;
        }

        $period = $otp->getPeriod();
        $now = time();

        $io->table(['', ''], [
            ['login', $user->getLogin()],
            ['method', (string) $config->method],
            ['status', $config->enabled ? 'enabled' : 'disabled'],
            ['code', $otp->at($now)],
            ['valid for', ($period - $now % $period) . ' of ' . $period . ' seconds'],
        ]);

        if ($input->getOption('secret')) {
            $io->text('secret: <comment>' . $otp->getSecret() . '</comment>');
            $io->text('uri:    <comment>' . $config->provisioningUri . '</comment>');
        }

        return 0;
    }
}
