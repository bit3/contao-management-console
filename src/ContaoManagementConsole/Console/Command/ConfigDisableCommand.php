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
use ContaoManagementConsole\Endpoint\Command\StatusCommands;
use ContaoManagementConsole\EndpointFactory;

class ConfigDisableCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('config:disable')
			->setDescription('Disable modules.')
			->addArgument(
			'modules',
			InputArgument::IS_ARRAY,
			'List of modules to disable.'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$modules = $input->getArgument('modules');

		$settings = $this->createSettings($input, $output);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->config->disable($modules);

		$this->outputErrors($result, $output);

		if (count($result->inactiveModules)) {
			$output->writeln('<info>disabled modules:</info>');
			foreach ($result->inactiveModules as $inactiveModule) {
				$output->writeln('- ' . $inactiveModule);
			}
		}
		else {
			$output->writeln('<info>no modules disabled</info>');
		}
	}
}
