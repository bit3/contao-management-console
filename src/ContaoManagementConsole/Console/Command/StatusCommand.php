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

use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use ContaoManagementConsole\Endpoint\Command\StatusCommands;
use ContaoManagementConsole\EndpointFactory;

class StatusCommand extends AbstractCommand
{
	protected $stabilities = array(
		'<alpha>alpha1</alpha>',
		'<alpha>alpha2</alpha>',
		'<alpha>alpha3</alpha>',
		'<beta>beta1</beta>',
		'<beta>beta2</beta>',
		'<beta>beta3</beta>',
		'<rc>rc1</rc>',
		'<rc>rc2</rc>',
		'<rc>rc3</rc>',
		'<stable>stable</stable>',
	);

	protected function configure()
	{
		parent::configure();

		$this
			->setName('status:summary')
			->setDescription('Fetch status summary of the installation.');
	}

	protected function setOutputFormats(OutputInterface $output)
	{
		$output
			->getFormatter()
			->setStyle('disabled', new OutputFormatterStyle('yellow'));
		$output
			->getFormatter()
			->setStyle('enabled', new OutputFormatterStyle(null));

		$output
			->getFormatter()
			->setStyle('alpha', new OutputFormatterStyle('magenta'));
		$output
			->getFormatter()
			->setStyle('beta', new OutputFormatterStyle('yellow'));
		$output
			->getFormatter()
			->setStyle('rc', new OutputFormatterStyle('blue'));
		$output
			->getFormatter()
			->setStyle('stable', new OutputFormatterStyle('green'));
		$output
			->getFormatter()
			->setStyle('noupdate', new OutputFormatterStyle('cyan'));

		$output
			->getFormatter()
			->setStyle('admin', new OutputFormatterStyle('blue'));
		$output
			->getFormatter()
			->setStyle('disabled', new OutputFormatterStyle('yellow'));
		$output
			->getFormatter()
			->setStyle('locked', new OutputFormatterStyle('magenta'));
	}

	protected function outputConsoleVersion(OutputInterface $output, $status)
	{
		if (isset($status->comaco) && (isset($status->comaco->version) || isset($status->comaco->date))) {
			$output->writeln('<info>Management console</info>');
			if (isset($status->comaco->version)) {
				$output->writeln('  - ' . $status->comaco->version);
			}
			if (isset($status->comaco->date)) {
				$output->writeln('  - ' . $status->comaco->date);
			}
		}
	}

	protected function outputContaoVersion(OutputInterface $output, $status)
	{
		$output->writeln('<info>Version</info>');
		$output->write('  - ' . $status->version . '.' . $status->build);
		if ($status->lts) {
			$output->write(' LTS');
		}
		$output->writeln('');
	}

	protected function outputModules(OutputInterface $output, $status)
	{
		$output->writeln('<info>Modules</info>');
		foreach ($status->modules as $module) {
			$output->write('  - ');
			if (in_array($module, $status->disabledModules)) {
				$output->write('<disabled>' . $module . ' (disabled)</disabled>');
			}
			else {
				$output->write('<enabled>' . $module . '</enabled>');
			}
			$output->writeln('');
		}
	}

	protected function outputExtensions(OutputInterface $output, $status)
	{
		$output->writeln('<info>Extensions</info>');
		$rows = array();
		foreach ($status->extensions as $extensionName => $extensionStatus) {
			$row = array(
				'name'    => $extensionName,
				'version' => $extensionStatus->version . ' ' . $this->stabilities[$extensionStatus->stability]
			);

			if ($extensionStatus->protected) {
				$row['version'] .= ' <noupdate>(noupdate)</noupdate>';;
			}
			else if ($extensionStatus->allowAlpha) {
				$row['version'] .= ' <alpha>(allow alpha)</alpha>';;
			}
			else if ($extensionStatus->allowBeta) {
				$row['version'] .= ' <beta>(allow beta)</beta>';;
			}
			else if ($extensionStatus->allowRC) {
				$row['version'] .= ' <rc>(allow rc)</rc>';;
			}

			$rows[] = $row;
		}

		/** @var TableHelper $tableHelper */
		$tableHelper = $this
			->getApplication()
			->getHelperSet()
			->get('table');
		$tableHelper->setHeaders(array('Extension', 'Version'));
		$tableHelper->setRows($rows);
		$tableHelper->render($output);
	}

	protected function outputUsers(OutputInterface $output, $status)
	{
		$output->writeln('<info>Users</info>');
		$rows = array();
		foreach ($status->users as $user) {
			$rows[] = array(
				'id'       => $user->id,
				'username' => $user->username,
				'name'     => $user->name,
				'email'    => $user->email,
				'admin'    => ($user->admin ? 'X' : ''),
				'status'   => ($user->disabled ? 'disabled' : ($user->locked ? 'locked' : '')),
			);
		}

		/** @var TableHelper $tableHelper */
		$tableHelper = $this
			->getApplication()
			->getHelperSet()
			->get('table');
		$tableHelper->setHeaders(array('ID', 'Username', 'Name', 'Email', 'Admin', 'Status'));
		$tableHelper->setRows($rows);
		$tableHelper->render($output);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setOutputFormats($output);

		$settings = $this->createSettings($input);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->status->summary();

		$this->outputErrors($result, $output);

		$status = $result->status;

		$this->outputConsoleVersion($output, $status);
		$this->outputContaoVersion($output, $status);
		$this->outputModules($output, $status);
		$this->outputExtensions($output, $status);
		$this->outputUsers($output, $status);
	}
}
