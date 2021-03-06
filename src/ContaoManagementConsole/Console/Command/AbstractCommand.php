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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ContaoManagementConsole\Settings;
use ContaoManagementConsole\EndpointFactory;

abstract class AbstractCommand extends Command
{
	protected function configure()
	{
		$this->addArgument(
			'target',
			InputArgument::REQUIRED,
			'Path to the contao installation or URL to the management api.'
		);
		$this->addOption(
			'private-key',
			'K',
			InputOption::VALUE_OPTIONAL,
			'Path to the private key file.'
		);
		$this->addOption(
			'public-key',
			'P',
			InputOption::VALUE_OPTIONAL,
			'Path to the public key file.'
		);
	}

	protected function createSettings(InputInterface $input)
	{
		$settings = new Settings();

		$settings->setPath(
			$input->getArgument('target')
		);

		$privateKeyFile = $input->getOption('private-key');
		if (!empty($privateKeyFile)) {
			$settings->setRsaLocalPrivateKey(
				file_get_contents($privateKeyFile)
			);
		}

		$publicKeyFile = $input->getOption('public-key');
		if (!empty($publicKeyFile)) {
			$settings->setRsaRemotePublicKey(
				file_get_contents($publicKeyFile)
			);
		}

		return $settings;
	}

	protected function createEndpoint(Settings $settings)
	{
		$factory = new EndpointFactory();
		/** @var \ContaoManagementConsole\Endpoint\Command\StatusCommands $endpoint */
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
