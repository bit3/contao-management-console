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

class LogsReadCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('logs:read')
			->setDescription('Read lines from a log file.')
			->addArgument(
			'log-file',
			InputArgument::REQUIRED,
			'Name of the log file to read.'
		)
			->addOption(
			'lines',
			'l',
			InputOption::VALUE_OPTIONAL,
			'Number of lines to read from the END of log file. Use negative number to read all lines.',
			100
		)
			->addOption(
			'offset',
			'o',
			InputOption::VALUE_OPTIONAL,
			'Skip number of lines.',
			0
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$file   = $input->getArgument('log-file');
		$lines  = (int) $input->getOption('lines');
		$offset = (int) $input->getOption('offset');

		$settings = $this->createSettings($input, $output);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->logs->read($file, $offset, $lines);

		$this->outputErrors($result, $output);

		$lines = $result->lines;

		if (count($lines)) {
			foreach ($lines as $line) {
				$output->writeln($line);
			}
		}
		else {
			$output->writeln('<comment>no lines found</comment>');
		}
	}
}
