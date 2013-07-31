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

namespace ContaoManagementConsole\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use ContaoManagementConsole\Endpoint\Command\StatusCommands;
use ContaoManagementConsole\EndpointFactory;

class ConfigSetCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('config:set')
			->setDescription('Set a config entry.')
			->addArgument(
			'key',
			InputArgument::REQUIRED,
			'The config entry key name.'
		)
			->addArgument(
			'value',
			InputArgument::REQUIRED,
			'The config entry key value.'
		)
		->addOption(
			'json-value',
			'j',
			InputOption::VALUE_NONE,
			'The value is a json string and must be decoded before set. Use this if you try to set arrays.'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$key = $input->getArgument('key');
		$value = $input->getArgument('value');

		if ($input->getOption('json-value')) {
			$value = json_decode($value);
		}
		else if ($value == 'true') {
			$value = true;
		}
		else if ($value == 'false') {
			$value = false;
		}
		else if (is_numeric($value)) {
			$value = strpos($value, '.') !== false
				? (double) $value
				: (int) $value;
		}

		$settings = $this->createSettings($input, $output);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->config->set($key, $value);

		$this->outputErrors($result, $output);

		if ($result->success) {
			$output->writeln('<info>config entry updated</info>');
		}
		else {
			$output->writeln('<error>could not update config entry</error>');
		}
	}
}
