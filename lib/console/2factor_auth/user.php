<?php

use rex_2fa\one_time_password;
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
    protected function configure()
    {
        $this
            ->setDescription('Deaktivates a 2factor_auth for a user')
            ->addArgument('user', InputArgument::REQUIRED, 'Username')
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

        if (!$user->getRows()) {
            throw new InvalidArgumentException(sprintf('User "%s" does not exist.', $username));
        }

        $user = rex_user::fromSql($user);
        $id = $user->getId();

        $io->text('User found: '.$user->getLogin());
        $io->text('Current status: '.$user->getLogin()."\n");






        return 0;

        $passwordPolicy = rex_backend_password_policy::factory();

        $password = $input->getArgument('password');

        if ($password && true !== $msg = $passwordPolicy->check($password, $id)) {
            throw new InvalidArgumentException($msg);
        }

        if (!$password) {
            $description = $passwordPolicy->getDescription();
            $description = $description ? ' ('.$description.')' : '';

            $password = $io->askHidden('Password'.$description, static function ($password) use ($id, $passwordPolicy) {
                if (true !== $msg = $passwordPolicy->check($password, $id)) {
                    throw new InvalidArgumentException($msg);
                }

                return $password;
            });
        }

        if (!$password) {
            throw new InvalidArgumentException('Missing password.');
        }

        return 0;


        $passwordHash = rex_backend_login::passwordHash($password);

        rex_sql::factory()
            ->setTable(rex::getTable('user'))
            ->setWhere(['id' => $id])
            ->setValue('password', $passwordHash)
            ->addGlobalUpdateFields('console')
            ->setDateTimeValue('password_changed', time())
            ->setArrayValue('previous_passwords', $passwordPolicy->updatePreviousPasswords($user, $passwordHash))
            ->setValue('password_change_required', (int) $input->getOption('password-change-required'))
            ->update();

        rex_extension::registerPoint(new rex_extension_point('PASSWORD_UPDATED', '', [
            'user_id' => $id,
            'user' => $user,
            'password' => $password,
        ], true));

        $io->success(sprintf('Saved new password for user "%s".', $username));

        return 0;
    }
}
