<?php

namespace Contao\Connector\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Contao\Connector\Settings;
use Contao\Connector\EndpointFactory;

abstract class AbstractCommand extends Command
{
	protected function configure()
	{
		$this->addArgument(
			'target',
			InputArgument::REQUIRED,
			'Path to the contao installation or URL to the connector.'
		);
		$this->addOption(
			'private-key',
			'k',
			InputOption::VALUE_OPTIONAL,
			'Path to the private key file.'
		);
		$this->addOption(
			'public-key',
			'p',
			InputOption::VALUE_OPTIONAL,
			'Path to the public key file.'
		);
	}

	protected function createSettings(InputInterface $input, OutputInterface $output)
	{
		$settings = new Settings();

		$settings->setPath(
			$input->getArgument('target')
		);

		if ($input->hasOption('private-key')) {
			$settings->setRsaLocalPrivateKey(
				file_get_contents($input->getOption('private-key'))
			);
		}

		if ($input->hasOption('public-key')) {
			$settings->setRsaRemotePublicKey(
				file_get_contents($input->getOption('public-key'))
			);
		}

		return $settings;
	}

	protected function createEndpoint(Settings $settings)
	{
		$factory = new EndpointFactory();
		/** @var \Contao\Connector\Command\StatusCommands $endpoint */
		$endpoint = $factory->createEndpoint($settings);

		return $endpoint;
	}

	protected function outputErrors($result, OutputInterface $output)
	{
		if (!empty($result->errors)) {
			foreach ($result->errors as $error) {
				$output->writeln('<error>' . str_pad('', strlen($error) + 10) . '</error>');
				$output->writeln('<error>     ' . $error . '     </error>');
				$output->writeln('<error>' . str_pad('', strlen($error) + 10) . '</error>');
			}
			$output->writeln('');
		}
	}
}
