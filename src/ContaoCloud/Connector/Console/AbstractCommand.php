<?php

namespace ContaoCloud\Connector\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ContaoCloud\Connector\Settings;

abstract class AbstractCommand extends Command
{
	protected function configure()
	{
		$this->addArgument(
			'path',
			InputArgument::REQUIRED,
			'Path to the contao installation.'
		);
	}

	protected function createSettings(InputInterface $input, OutputInterface $output) {
		$settings = new Settings();

		$settings->setPath(
			$input->getArgument('path')
		);

		return $settings;
	}
}
