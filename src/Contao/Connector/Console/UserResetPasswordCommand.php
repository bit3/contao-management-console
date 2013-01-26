<?php

namespace Contao\Connector\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Contao\Connector\Command\UserCommands;
use Contao\Connector\EndpointFactory;

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
