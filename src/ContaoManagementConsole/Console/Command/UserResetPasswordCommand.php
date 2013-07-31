<?php

/**
 * Management Console for Contao Open Source CMS
 *
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    contao-management-console
 * @license    LGPL-3.0+
 * @filesource
 */

namespace ContaoManagementConsole\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use ContaoManagementConsole\Endpoint\Command\UserCommands;
use ContaoManagementConsole\EndpointFactory;

class UserResetPasswordCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('user:reset-password')
			->setDescription('Reset the password of a backend user.')
			->addOption(
				'user',
				'u',
				InputOption::VALUE_OPTIONAL,
				'The id, username or email of the user.'
			)
			->addOption(
				'password',
				'p',
				InputOption::VALUE_OPTIONAL,
				'The new password for the user.'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$userIdentifier = $input->getOption('user');

		if (empty($userIdentifier)) {
			// TODO read from terminal
			throw new \Exception('Not yet implemented!');
		}

		if (empty($userIdentifier)) {
			$output->writeln('Missing user identifier!');
			exit;
		}

		$password = $input->getOption('password');

		if (empty($password)) {
			// TODO read from terminal
			throw new \Exception('<error>Not yet implemented!</error>');
		}

		if (empty($password)) {
			$output->writeln('<error>Missing user password!</error>');
			exit;
		}

		$settings = $this->createSettings($input, $output);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->user->resetPassword($userIdentifier, $password);

		$this->outputErrors($result, $output);

		if ($result->success) {
			$output->writeln('<info>                                     </info>');
			$output->writeln('<info>     Password reset successfully     </info>');
			$output->writeln('<info>                                     </info>');
		}
	}
}
