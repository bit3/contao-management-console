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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;
use Composer\Autoload\ClassLoader;
use Filicious\File;
use Filicious\Filesystem;
use Filicious\Local\LocalAdapter;
use Symfony\Component\Process\Process;
use Traversable;

class UpdateCommand extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('update')
			->setDescription('Update remote phar with self');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!defined('COMACO_FILE')) {
			throw new \RuntimeException('Own filename is not provided, do you run update from phar?');
		}

		$filesystem = new Filesystem(new LocalAdapter(getcwd()));
		$file = $filesystem->getFile(COMACO_FILE);

		if (!$file->exists()) {
			throw new \RuntimeException('Could not find own file in filesystem, are you running phar from current working directory?');
		}

		$phar = $file->getContents();
		$phar = base64_encode($phar);

		$settings = $this->createSettings($input);
		$endpoint = $this->createEndpoint($settings);

		$result = $endpoint->selfUpdate($phar);

		$output->writeln('  <info>*</info> Updated remote hash ' . $result);
	}
}
