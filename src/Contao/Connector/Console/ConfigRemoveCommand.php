<?php

namespace Contao\Connector\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigRemoveCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('config:remove')
			->setDescription('Remove a config entry.')
			->addArgument(
			'key',
			InputArgument::REQUIRED,
			'The config entry key name.'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$key = $input->getArgument('key');

		$settings = $this->createSettings($input, $output);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->config->remove($key);

		$this->outputErrors($result, $output);

		if ($result->success) {
			$output->writeln('<info>config entry removed</info>');
		}
		else {
			$output->writeln('<error>could not remove config entry</error>');
		}
	}
}