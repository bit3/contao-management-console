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

use RemoteObjects\Client;
use RemoteObjects\Transport\CurlClient;
use RemoteObjects\Encode\JsonRpc20Encoder;
use RemoteObjects\Encode\RsaEncoder;
use ContaoManagementConsole\Command\ConfigCommands;
use ContaoManagementConsole\Command\LogsCommands;
use ContaoManagementConsole\Command\StatusCommands;
use ContaoManagementConsole\Command\SyslogCommands;
use ContaoManagementConsole\Command\UserCommands;

class EndpointFactory
{
	protected $logger;

	/**
	 * @param Settings $settings
	 *
	 * @return \RemoteObjects\Client|\stdClass
	 */
	public function createEndpoint(Settings $settings)
	{
		$url = parse_url($settings->getPath());

		// local call
		if (empty($url['scheme']) || $url['scheme'] == 'file') {
			$endpoint         = new \stdClass();
			$endpoint->config = new ConfigCommands($settings);
			$endpoint->logs   = new LogsCommands($settings);
			$endpoint->status = new StatusCommands($settings);
			$endpoint->syslog = new SyslogCommands($settings);
			$endpoint->user   = new UserCommands($settings);
		}

		// remote call
		else {
			$transport = new CurlClient($settings->getPath());

			$encoder = new JsonRpc20Encoder();

			if ($settings->isEncryptionEnabled()) {
				$encoder = new RsaEncoder(
					$encoder,
					$settings->getRsaRemotePublicKey(),
					$settings->getRsaLocalPrivateKey()
				);
			}

			$client = new Client(
				$transport,
				$encoder
			);

			$endpoint = $client->castAsRemoteObject();
		}

		return $endpoint;
	}
}