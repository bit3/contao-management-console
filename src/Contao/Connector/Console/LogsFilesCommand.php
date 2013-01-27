<?php

namespace Contao\Connector\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogsFilesCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('logs:files')
			->setDescription('List all available log files.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$settings = $this->createSettings($input, $output);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->logs->files();

		$this->outputErrors($result, $output);

		$files = $result->files;

		if (count($files)) {
			foreach ($files as $file => $details) {
				$output->writeln('- ' . $file);
				$output->writeln('  size: ' . number_format($details->size) . ' bytes');
				$output->writeln('  line count: ' . number_format($details->lines));
				$output->writeln('  last modified: ' . $details->modified);
			}
		}
		else {
			$output->writeln('<comment>no files found</comment>');
		}
	}
}
