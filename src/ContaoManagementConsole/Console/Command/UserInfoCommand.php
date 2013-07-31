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
use ContaoManagementConsole\Endpoint\Command\UserCommands;
use ContaoManagementConsole\EndpointFactory;

class UserInfoCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('user:info')
			->setDescription('Fetch users and groups from the installation.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
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

		$result = $endpoint->user->info();

		$this->outputErrors($result, $output);

		$users  = $result->users;
		$groups = $result->groups;

		$output->writeln('<info>Users</info>');
		$paddings = $this->calculatePadding($users, array('id', 'username', 'name'));
		foreach ($users as $user) {
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
			else if ($user->disable) {
				$line .= ' <disabled>(disabled)</disabled>';
			}

			$output->write('  - ');
			$output->writeln($line);

			if (!$user->admin && count($user->groups)) {
				$groupNames = array();
				foreach ($user->groups as $groupId) {
					if (isset($groups->$groupId)) {
						$groupNames[] = $groups->$groupId->name;
					}
				}
				$output->writeln(str_pad('', $paddings['id']+6) . ' member of [' . implode(', ', $groupNames) . ']');
			}
		}

		$output->writeln('<info>Groups</info>');
		$paddings = $this->calculatePadding($groups, array('id', 'name'));
		foreach ($groups as $group) {
			// id,username,name,email,admin,disable AS disabled,locked,currentLogin
			$line = '  - ' . str_pad('[' . $group->id . ']', $paddings['id']);
			$line .= ' ' . str_pad($group->name, $paddings['name']);
			if (
				$user->disable ||
				!empty($group->start) && $group->start > time() ||
				!empty($group->stop) && $group->stop < time()
			) {
				$line .= ' <disabled>(disabled)</disabled>';
			}

			$output->writeln($line);

			$output->writeln(str_pad('', $paddings['id']+6) . ' has access to [' . implode(', ', $group->modules) . ']');
		}
	}
}
