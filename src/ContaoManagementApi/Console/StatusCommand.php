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

namespace ContaoManagementApi\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use ContaoManagementApi\Command\StatusCommands;
use ContaoManagementApi\EndpointFactory;

class StatusCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('status:summary')
			->setDescription('Fetch status summary of the installation.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
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

		$settings = $this->createSettings($input, $output);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->status->summary();

		$this->outputErrors($result, $output);

		$status = $result->status;

		$output->writeln('<info>Version</info>');
		$output->write('  - ' . $status->version . '.' . $status->build);
		if ($status->lts) {
			$output->write(' LTS');
		}
		$output->writeln('');

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

		$output->writeln('<info>Extensions</info>');
		$paddings = $this->calculatePadding($status->extensions, array('name', 'version'));
		foreach ($status->extensions as $extensionName => $extensionStatus) {
			$output->write('  - ' . str_pad($extensionName, $paddings['name']));
			$output->write(' ' . str_pad($extensionStatus->version, $paddings['version']));
			switch ($extensionStatus->stability) {
				case '0':
					$output->write(' <alpha>alpha1</alpha>');
					break;
				case '1':
					$output->write(' <alpha>alpha2</alpha>');
					break;
				case '2':
					$output->write(' <alpha>alpha3</alpha>');
					break;
				case '3':
					$output->write(' <beta>beta1</beta> ');
					break;
				case '4':
					$output->write(' <beta>beta1</beta> ');
					break;
				case '5':
					$output->write(' <beta>beta3</beta> ');
					break;
				case '6':
					$output->write(' <rc>rc1</rc>   ');
					break;
				case '7':
					$output->write(' <rc>rc2</rc>   ');
					break;
				case '8':
					$output->write(' <rc>rc3</rc>   ');
					break;
				case '9':
					$output->write(' <stable>stable</stable>');
					break;
			}
			if ($extensionStatus->protected) {
				$output->write(' <noupdate>(noupdate)</noupdate>');
			}
			else if ($extensionStatus->allowAlpha) {
				$output->write(' <alpha>(allow alpha)</alpha>');
			}
			else if ($extensionStatus->allowBeta) {
				$output->write(' <beta>(allow beta)</beta>');
			}
			else if ($extensionStatus->allowRC) {
				$output->write(' <rc>(allow rc)</rc>');
			}
			$output->writeln('');
		}

		$output->writeln('<info>Users</info>');
		$paddings = $this->calculatePadding($status->users, array('id', 'username', 'name'));
		foreach ($status->users as $user) {
			// id,username,name,email,admin,disable AS disabled,locked,currentLogin
			$line = str_pad('[' . $user->id . ']', $paddings['id']);
			$line .= ' ' . str_pad($user->username, $paddings['username']);
			$line .= '  ' . str_pad($user->name, $paddings['name']);
			$line .= '  ' . $user->email;

			if ($user->admin) {
				$line = '<admin>' . $line . '</admin>';
			}

			if ($user->locked) {
				$line .= ' <locked>(locked)</locked>';
			}
			else if ($user->disabled) {
				$line .= ' <disabled>(disabled)</disabled>';
			}

			$output->write('  - ');
			$output->writeln($line);
		}
	}
}
