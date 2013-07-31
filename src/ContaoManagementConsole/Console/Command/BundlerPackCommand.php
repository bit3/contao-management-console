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

class BundlerPackCommand extends Command
{
	/**
	 * @var InputInterface
	 */
	protected $input;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * The contao management console's version
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * The contao management console's version date
	 *
	 * @var string
	 */
	protected $date;

	/**
	 * @var string
	 */
	protected $basepath;

	/**
	 * @var string
	 */
	protected $vendorDir;

	/**
	 * @var string
	 */
	protected $ownpath;

	/**
	 * @var \Phar
	 */
	protected $phar;

	/**
	 * @var Filesystem
	 */
	protected $filesystem;

	protected function configure()
	{
		parent::configure();

		$this
			->setName('bundler:pack')
			->setDescription('Create bundled executable')
			->addArgument(
				'output',
				InputArgument::REQUIRED,
				'Write to this file'
			)
			->addOption(
				'private-key-file',
				'K',
				InputOption::VALUE_REQUIRED,
				'Path to the private key file'
			)
			->addOption(
				'private-key',
				null,
				InputOption::VALUE_REQUIRED,
				'The private key'
			)
			->addOption(
				'public-key-file',
				'P',
				InputOption::VALUE_REQUIRED,
				'Path to the public key file'
			)
			->addOption(
				'public-key',
				null,
				InputOption::VALUE_REQUIRED,
				'The public key'
			)
			->addOption(
				'contao-path',
				'p',
				InputOption::VALUE_REQUIRED,
				'Relative path from the management api to the contao installation base path',
				'../'
			)
			->addOption(
				'log',
				'l',
				InputOption::VALUE_REQUIRED,
				'Relative path from the management api to the log file (e.g. connect.log)'
			)
			->addOption(
				'log-name',
				'N',
				InputOption::VALUE_REQUIRED,
				'Logger name',
				'contao-management-api'
			)
			->addOption(
				'log-level',
				'L',
				InputOption::VALUE_REQUIRED,
				'Set the log level [DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY]',
				'ERROR'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$out = $input->getArgument('output');

		$this->input = $input;
		$this->output = $output;

		$this->detectPaths();
		$this->detectSelfVersion();
		$this->initializePhar($out);
		$this->createPhar();
		$this->finalizePhar($out);
	}

	protected function detectPaths()
	{
		$dir = __DIR__;

		while ($dir && $dir != '.' && $dir != '/' && !is_file($dir . '/vendor/autoload.php')) {
			$dir = dirname($dir);
		}

		if (!is_file($dir . '/vendor/autoload.php')) {
			throw new \RuntimeException('Could not find vendor/autoload.php');
		}

		$ownpath = __DIR__;

		while ($ownpath && $ownpath != '.' && $ownpath != '/' && !is_file($ownpath . '/composer.json')) {
			$ownpath = dirname($ownpath);
		}

		if (!is_file($ownpath . '/composer.json')) {
			throw new \RuntimeException('Could not find own composer.json');
		}

		$this->basepath = $dir;
		$this->vendorDir = $dir . '/vendor';
		$this->ownpath = $ownpath;

		$this->filesystem = new Filesystem(new LocalAdapter('/'));
	}

	protected function detectSelfVersion()
	{
		$process = new Process('git log --pretty="%H" -n1 HEAD', __DIR__);
		if ($process->run() != 0) {
			throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from git repository clone and that git binary is available.');
		}
		$this->version = trim($process->getOutput());

		$process = new Process('git log --pretty="%ai" -n1 HEAD', __DIR__);
		if ($process->run() != 0) {
			throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from composer git repository clone and that git binary is available.');
		}
		$this->date = trim($process->getOutput());

		$process = new Process('git describe --tags HEAD');
		if ($process->run() == 0) {
			$this->version = trim($process->getOutput());
		}
	}

	protected function initializePhar($filename)
	{
		if (file_exists($filename)) {
			unlink($filename);
		}
		
		$this->phar = new \Phar($filename);
		$this->phar->setSignatureAlgorithm(\Phar::SHA1);

		$this->phar->startBuffering();
	}

	protected function createPhar()
	{
		$composerJsonFile = $this->filesystem->getFile($this->ownpath . '/composer.json');
		$composerJson     = $composerJsonFile->getContents();
		$composerConfig   = json_decode($composerJson);

		$src = $this->filesystem->getFile($this->ownpath . '/src');
		$this->addFilesFrom($src, 'src');

		foreach ($composerConfig->require as $packageName => $packageConstraint) {
			if ($packageName == 'php') {
				continue;
			}

			$packageDir = $this->filesystem->getFile($this->vendorDir . '/' . $packageName);

			if ($packageDir->isDirectory()) {
				$this->addFilesFrom($packageDir, 'vendor/' . $packageName);
			}
			else {
				$this->output->writeln(
					' <comment>*</comment> Package ' . $packageName . ' does not exist in ' . $packageDir->getPathname()
				);
			}
		}

		$this->addFile(
			$this->filesystem->getFile($this->ownpath . '/scripts/error_handler.php'),
			'scripts/error_handler.php'
		);
		$this->addFile($this->filesystem->getFile($this->ownpath . '/bin/contaoctl'), 'bin/contaoctl.php', true);
		$this->addFile($this->filesystem->getFile($this->ownpath . '/scripts/connect.php'), 'scripts/connect.php');
		$this->addFile($this->filesystem->getFile($this->vendorDir . '/autoload.php'), 'vendor/autoload.php');
		$this->addFilesFrom($this->filesystem->getFile($this->vendorDir . '/composer'), 'vendor/composer');
	}

	protected function addFilesFrom(File $directory, $into)
	{
		$this->output->writeln(' <info>*</info> Add php files from ' . $directory->getPathname());

		$files = $directory->getIterator(File::LIST_RECURSIVE);

		/** @var File $file */
		foreach ($files as $file) {
			if (fnmatch('*.php', $file->getPathname()) &&
				!fnmatch('*/test/*', $file->getPathname()) &&
				!fnmatch('*/tests/*', $file->getPathname()) &&
				$file->getBasename() != 'BundlerPackCommand.php'
			) {
				$path = $into . '/' . ltrim(
						str_replace($directory->getPathname(), '', $file->getPathname()),
						'/'
					);

				$this->addFile($file, $path);
			}
		}
	}

	protected function addFile(File $file, $path, $stripBin = false)
	{
		$this->output->writeln(' <info>*</info> Add php file ' . $path);

		$content = $file->getContents();

		if ($stripBin) {
			$content = preg_replace('~^#!/usr/bin/env php\s*~', '', $content);
			$content = str_replace('$application->add(new BundlerPackCommand);', '', $content);
		}

		$this->phar->addFromString($path, $content);
	}

	protected function finalizePhar($filename)
	{
		$stub = $this->createStub($filename);

		$this->phar->stopBuffering();
		$this->phar->setStub($stub);
	}

	protected function createStub($filename)
	{
		$basename = basename($filename);
		$version  = var_export($this->version, true);
		$date     = var_export($this->date, true);

		$stub = <<<EOF
#!/usr/bin/env php
<?php
/*
 * This file is a build of the Management Console for Contao Open Source CMS
 */

Phar::mapPhar('$basename');

define('COMACO_VERSION', {$version});
define('COMACO_DATE', {$date});

EOF;

		$privateKeyFile = $this->input->getOption('private-key-file');
		$privateKey     = $this->input->getOption('private-key');
		$publicKeyFile  = $this->input->getOption('public-key-file');
		$publicKey      = $this->input->getOption('public-key');
		$contaoPath     = $this->input->getOption('contao-path');
		$log            = $this->input->getOption('log');
		$logName        = $this->input->getOption('log-name');
		$logLevel       = $this->input->getOption('log-level');

		// add constants to buffer
		if ($privateKeyFile && file_exists($privateKeyFile)) {
			$privateKey = file_get_contents($privateKeyFile);
		}
		if ($privateKey) {
			$this->output->writeln(' <info>*</info> Add private key');

			$privateKey = var_export($privateKey, true);

			$stub .= <<<EOF
define('COMACO_RSA_LOCAL_PRIVATE_KEY', $privateKey);

EOF;
		}

		if ($publicKeyFile && file_exists($publicKeyFile)) {
			$publicKey = file_get_contents($publicKeyFile);
		}
		if ($publicKey) {
			$this->output->writeln(' <info>*</info> Add public key');

			$publicKey = var_export($publicKey, true);

			$stub .= <<<EOF
define('COMACO_RSA_REMOTE_PUBLIC_KEY', $publicKey);

EOF;
		}

		$contaoPath = var_export('/' . $contaoPath, true);
		$stub .= <<<EOF
define('COMACO_CONTAO_PATH', realpath(dirname(__FILE__) . $contaoPath));

EOF;

		if ($log) {
			$this->output->writeln(' <info>*</info> Activate logging to ' . $log);

			$log = var_export('/' . $log, true);

			$logLevel = strtoupper($logLevel);
			$class    = new \ReflectionClass('\Monolog\Logger');
			if ($class->hasConstant($logLevel)) {
				$logLevel = $class->getConstant($logLevel);
			}
			else {
				$logLevel = (int) $logLevel;
			}

			$stub .= <<<EOF
define('COMACO_LOG', dirname(__FILE__) . $log);
define('COMACO_LOG_LEVEL', $logLevel);

EOF;

			if ($logName != 'contao-management-api') {
				$logName = var_export($logName, true);
				$stub .= <<<EOF
define('COMACO_LOG_NAME', $logName);

EOF;
			}
		}


		$stub .= <<<EOF

require 'phar://$basename/scripts/error_handler.php';

if (PHP_SAPI == 'cli') {
	require 'phar://$basename/bin/contaoctl.php';
}
else {
	require 'phar://$basename/scripts/connect.php';
}

__HALT_COMPILER();
EOF;

		return $stub;
	}
}
