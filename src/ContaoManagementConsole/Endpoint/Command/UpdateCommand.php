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

namespace ContaoManagementConsole\Endpoint\Command;

use Filicious\File;
use Filicious\Filesystem;
use Filicious\Local\LocalAdapter;
use PDO;
use ContaoManagementConsole\Settings;

class UpdateCommand extends AbstractCommands
{
	public function selfUpdate($phar)
	{
		if (!defined('COMACO_FILE')) {
			throw new \RuntimeException('Own filename is not provided, do you run update from phar?');
		}

		$filesystem = new Filesystem(new LocalAdapter(getcwd()));
		$file = $filesystem->getFile(COMACO_FILE);

		if (!$file->exists()) {
			throw new \RuntimeException('Could not find own file in filesystem, are you running phar from current working directory?');
		}
		if (!$file->isWritable()) {
			throw new \RuntimeException(COMACO_FILE . ' is not writeable!');
		}

		$tempDir = sys_get_temp_dir();
		$tempFilesystem = new Filesystem(new LocalAdapter($tempDir));
		$root = $tempFilesystem->getRoot();

		if (!$root->isWritable()) {
			throw new \RuntimeException('Temporary directory is not writeable!');
		}

		$tempFileIndex = 0;

		do {
			$tempFile = $tempFilesystem->getFile('/comaco' . ($tempFileIndex > 0 ? '_' . $tempFileIndex : '') . '.phar');
			$tempFileIndex ++;
		} while ($tempFile->exists());

		$phar = base64_decode($phar);
		$tempFile->setContents($phar);

		// test the archive
		$realPath = $tempDir . '/' . $tempFile->getBasename();
		new \Phar($realPath);

		$tempFile->moveTo($file, File::OPERATION_REPLACE);

		return $file->getMD5();
	}
}
