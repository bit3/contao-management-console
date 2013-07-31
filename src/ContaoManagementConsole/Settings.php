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

namespace ContaoManagementConsole;

class Settings {
	/**
	 * Path to the contao installation.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $rsaLocalPrivateKey;

	/**
	 * @var string
	 */
	protected $rsaRemotePublicKey;

	function __construct()
	{
		$this->path = getcwd();
		$this->rsaLocalPrivateKey = null;
		$this->rsaRemotePublicKey = null;
	}

	/**
	 * @param string $path
	 *
	 * @return Settings
	 */
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param $rsaLocalPrivateKey
	 *
	 * @return Settings
	 */
	public function setRsaLocalPrivateKey($rsaLocalPrivateKey)
	{
		$this->rsaLocalPrivateKey = $rsaLocalPrivateKey;
		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getRsaLocalPrivateKey()
	{
		return $this->rsaLocalPrivateKey;
	}

	/**
	 * @param $rsaRemotePublicKey
	 *
	 * @return Settings
	 */
	public function setRsaRemotePublicKey($rsaRemotePublicKey)
	{
		$this->rsaRemotePublicKey = $rsaRemotePublicKey;
		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getRsaRemotePublicKey()
	{
		return $this->rsaRemotePublicKey;
	}

	/**
	 * @return bool
	 */
	public function isEncryptionEnabled()
	{
		return !empty($this->rsaLocalPrivateKey) && !empty($this->rsaRemotePublicKey);
	}
}
