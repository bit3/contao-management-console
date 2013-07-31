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

use ContaoManagementConsole\Endpoint\Command\UpdateCommand;
use ContaoManagementConsole\Endpoint\Command\StatusCommands;

class Endpoint
{
	/**
	 * @var Settings
	 */
	protected $settings;

	function __construct(Settings $settings)
	{
		$this->settings = $settings;
	}

	public function selfUpdate()
	{
		$updateCommand = new UpdateCommand($this->settings);
		$args = func_get_args();
		return call_user_func_array(array($updateCommand, 'selfUpdate'), $args);
	}

	public function getStatus()
	{
		return new StatusCommands($this->settings);
	}
}