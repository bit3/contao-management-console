<?php

namespace ContaoManagementApi\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use ContaoManagementApi\Command\StatusCommands;
use ContaoManagementApi\EndpointFactory;

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
