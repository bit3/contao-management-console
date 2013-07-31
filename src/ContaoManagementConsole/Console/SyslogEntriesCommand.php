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

class SyslogEntriesCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('syslog:entries')
			->setDescription('Fetch syslog records.')
			->addOption(
			'day',
			'd',
			InputOption::VALUE_OPTIONAL,
			'Fetch records on day, possible values: a datetime string or timestamp.'
		)
			->addOption(
			'since',
			's',
			InputOption::VALUE_OPTIONAL,
			'Fetch records since date, possible values: a datetime string or timestamp.'
		)
			->addOption(
			'until',
			't',
			InputOption::VALUE_OPTIONAL,
			'Filter records until date, possible values: a datetime string or timestamp.'
		)
			->addOption(
			'source',
			'e',
			InputOption::VALUE_OPTIONAL,
			'Filter records by log source, possible values: [FE, BE]'
		)
			->addOption(
			'action',
			'a',
			InputOption::VALUE_OPTIONAL,
			'Filter records by log action, possible values: [GENERAL, ACCESS, CRON, CONFIGURATION, REPOSITORY, ..]'
		)
			->addOption(
			'username',
			'u',
			InputOption::VALUE_OPTIONAL,
			'Filter records by log username, possible values: a valid backend user username.'
		)
			->addOption(
			'func',
			'f',
			InputOption::VALUE_OPTIONAL,
			'Filter records by log function, possible values: a function name'
		)
			->addOption(
			'ip',
			'i',
			InputOption::VALUE_OPTIONAL,
			'Filter records by log ip, possible values: a valid ip address'
		)
			->addOption(
			'limit',
			'l',
			InputOption::VALUE_OPTIONAL,
			'Limit returned record count.',
			100
		)
			->addOption(
			'offset',
			'o',
			InputOption::VALUE_OPTIONAL,
			'Skip records.',
			0
		)
			->addOption(
			'columns',
			'c',
			InputOption::VALUE_OPTIONAL,
			'Shown columns.',
			'id,tstamp,source,action,username,text,func,ip'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output
			->getFormatter()
			->setStyle('cron', new OutputFormatterStyle('green'));
		$output
			->getFormatter()
			->setStyle('repository', new OutputFormatterStyle('blue'));
		$output
			->getFormatter()
			->setStyle('configuration', new OutputFormatterStyle('blue'));
		$output
			->getFormatter()
			->setStyle('error', new OutputFormatterStyle('red'));
		$output
			->getFormatter()
			->setStyle('access', new OutputFormatterStyle('yellow'));

		$filter = new \stdClass();

		$day = $input->getOption('day');
		if (!empty($day)) {
			$filter->on = $day;
		}
		$since = $input->getOption('since');
		if (!empty($since)) {
			$filter->since = $since;
		}
		$until = $input->getOption('until');
		if (!empty($until)) {
			$filter->until = $until;
		}
		$source = $input->getOption('source');
		if (!empty($source)) {
			$filter->source = $source;
		}
		$action = $input->getOption('action');
		if (!empty($action)) {
			$filter->action = $action;
		}
		$username = $input->getOption('username');
		if (!empty($username)) {
			$filter->username = $username;
		}
		$func = $input->getOption('func');
		if (!empty($func)) {
			$filter->func = $func;
		}
		$ip = $input->getOption('ip');
		if (!empty($ip)) {
			$filter->ip = $ip;
		}
		$limit = $input->getOption('limit');
		if (!empty($limit)) {
			$filter->limit = $limit;
		}
		$offset = $input->getOption('offset');
		if (!empty($offset)) {
			$filter->offset = $offset;
		}

		$settings = $this->createSettings($input, $output);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->syslog->entries($filter);


		$this->outputErrors($result, $output);

		$syslog = $result->syslog;

		if (count($syslog)) {
			$fields = explode(',', $input->getOption('columns'));

			foreach ($syslog as $row) {
				$row->text = html_entity_decode($row->text, ENT_QUOTES, 'UTF-8');
			}

			$padding = $this->calculatePadding($syslog, $fields, true);

			$firstColumn = true;
			foreach ($fields as $field) {
				if ($firstColumn) {
					$firstColumn = false;
				}
				else {
					$output->write(' | ');
				}
				$output->write(str_pad($field, $padding[$field]));
			}
			$output->writeln('');

			$firstColumn = true;
			foreach ($fields as $field) {
				if ($firstColumn) {
					$firstColumn = false;
				}
				else {
					$output->write('-|-');
				}
				$output->write(str_pad('', $padding[$field], '-'));
			}
			$output->writeln('');

			foreach ($syslog as $row) {
				$firstColumn = true;
				$line = '';
				foreach ($fields as $field) {
					if ($firstColumn) {
						$firstColumn = false;
					}
					else {
						$line .= ' | ';
					}
					$line .= str_pad($row->$field, $padding[$field]);
				}
				$style = strtolower($row->action);
				if ($output->getFormatter()->hasStyle($style)) {
					$line = sprintf(
						'<%s>%s</%s>',
						$style,
						$line,
						$style
					);
				}
				$output->writeln($line);
			}
		}
		else {
			$output->writeln('<comment>no records found</comment>');
		}
	}
}
