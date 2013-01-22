<?php

namespace ContaoCloud\Connector\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use ContaoCloud\Connector\Command\UserInfoCommandRequest;
use ContaoCloud\Connector\CommandRequestFactory;

class UserinfoCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('userinfo')
			->setDescription('Fetch users and groups from the installation.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$settings = $this->createSettings($input, $output);

		$factory = new CommandRequestFactory();
		/** @var \ContaoCloud\Connector\Command\UserInfoCommandRequest $request */
		$request = $factory->create($settings, 'user:info', null);
		/** @var \ContaoCloud\Connector\Command\UserInfoCommandResponse $response */
		$response = $request->execute($settings);

		$output
			->getFormatter()
			->setStyle('admin', new OutputFormatterStyle('blue'));
		$output
			->getFormatter()
			->setStyle('disabled', new OutputFormatterStyle('yellow'));
		$output
			->getFormatter()
			->setStyle('locked', new OutputFormatterStyle('magenta'));

		if (count($response->errors())) {
			foreach ($response->errors() as $error) {
				$output->writeln('<error>' . $error . '</error>');
			}
			$output->writeln('');
		}

		$users  = $response->getUsers();
		$groups = $response->getGroups();

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
					$groupNames[] = $groups[$groupId]->name;
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

	protected function calculatePadding($rows, $fields = null)
	{
		$paddings = array();
		foreach ($rows as $row) {
			foreach ($row as $key => $value) {
				if (is_string($value) && ($fields === null || in_array($key, $fields))) {
					$paddings[$key] = max(
						strlen($value),
						isset($paddings[$key]) ? $paddings[$key] : 0
					);
				}
			}
		}
		return $paddings;
	}
}
