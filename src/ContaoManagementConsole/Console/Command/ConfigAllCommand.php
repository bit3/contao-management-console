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

class ConfigAllCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('config:all')
			->setDescription('Get all config entries with value.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$settings = $this->createSettings($input, $output);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->config->all();

		$this->outputErrors($result, $output);

		$config = $result->config;

		$pad = 0;
		foreach ($config as $key => $value) {
			$pad = max(strlen($key), $pad);
		}
		foreach ($config as $key => $value) {
			$output->write(
				sprintf(
					'<comment>%s</comment>',
					str_pad($key, $pad, ' ', STR_PAD_LEFT)
				)
			);
			$output->write(': ');
			$output->writeln($this->formatValueForOutput($value));
		}
	}
}
