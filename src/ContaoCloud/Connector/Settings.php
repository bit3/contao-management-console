<?php

namespace ContaoCloud\Connector;

class Settings {
	/**
	 * Path to the contao installation.
	 *
	 * @var string
	 */
	protected $path;

	function __construct()
	{
		$this->path = dirname(getcwd());
	}

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = realpath($path);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}
}
