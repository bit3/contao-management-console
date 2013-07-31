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

namespace ContaoManagementConsole\Command;

use PDO;
use ContaoManagementConsole\Settings;
use Filicious\File;
use Filicious\Stream\StreamMode;

class LogsCommands extends AbstractCommands
{
	public function files()
	{
		$files = array();

		if ($this->prepareFilesystemAccess()) {
			$logsDirectory = $this->contaoInstallation->getFile('system/logs');

			if (!$logsDirectory->isDirectory()) {
				$this->errors[] = 'Log directory system/logs does not exists!';
			}
			else {
				/** @var \Filicious\File $logFile */
				foreach ($logsDirectory->getIterator(File::LIST_VISIBLE) as $logFile) {
					$lineCount = 0;

					// save line count calculation
					$stream = $logFile->getStream();
					$stream->open(new StreamMode('rb'));
					while (!$stream->eof()) {
						$content = $stream->read(1024);

						$lastPos = 0;
						while (($pos = strpos($content, "\n", $lastPos)) !== false) {
							$lineCount++;
							$lastPos = $pos + 1;
						}

						unset($content);
					}

					$files[$logFile->getBasename()] = (object) array(
						'size'     => $logFile->getSize(),
						'lines'    => $lineCount,
						'modified' => $logFile
							->getModifyTime()
							->format('c'),
					);
				}
			}
		}

		return (object) array(
			'files'  => (object) $files,
			'errors' => $this->errors
		);
	}

	/**
	 * Read the last lines from the error log.
	 *
	 * @param string $file
	 * @param int    $offsetLines Number of lines to skip (backwards)
	 * @param int    $lineCount
	 *
	 * @return object
	 */
	public function read($file, $offsetLines = 0, $lineCount = 100)
	{
		$lines = array();

		if ($this->prepareFilesystemAccess()) {
			$logFile = $this->contaoInstallation->getFile('system/logs/' . $file);

			if (!$logFile->isFile()) {
				$this->errors[] = sprintf(
					'Log file system/logs/%s does not exists!',
					$file
				);
			}
			else {
				$stream = $logFile->getStream();
				$stream->open(new StreamMode('rb'));

				$size    = $logFile->getSize();
				$read    = 0;
				$skipped = 0;

				// seek to the end
				$stream->seek(0, SEEK_END);

				$content = '';

				while (
					$read < $size &&
					($lineCount < 0 || count($lines) < $lineCount)
				) {
					$newSeekPosition = ($size - $read) >= 1024
						? $size - $read - 1024
						: 0;
					$toRead          = ($size - $read) >= 1024
						? 1024
						: $size - $read;

					$stream->seek($newSeekPosition);
					$content = $stream->read($toRead) . $content;
					$read += $toRead;

					while (
						($pos = strrpos($content, "\n")) !== false &&
						($lineCount < 0 || count($lines) < $lineCount)
					) {
						// extract line from end of content
						$line    = trim(substr($content, $pos));
						$content = substr($content, 0, $pos);

						// skip empty lines
						if (empty($line)) {
							continue;
						}

						// skip offset lines
						else if ($skipped < $offsetLines) {
							$skipped++;
						}

						// add line
						else {
							$lines[] = $line;
						}
					}

					// append the first line
					if (
						$pos === false &&
						($lineCount < 0 || count($lines) < $lineCount) &&
						$size == $read
					) {
						$lines[] = $content;
					}
				}
			}
		}

		return (object) array(
			'lines'  => array_reverse($lines),
			'errors' => $this->errors
		);
	}
}
