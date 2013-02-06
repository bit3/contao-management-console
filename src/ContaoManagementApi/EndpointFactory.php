<?php

namespace ContaoManagementApi;

use RemoteObjects\Client;
use RemoteObjects\Transport\CurlClient;
use RemoteObjects\Encode\JsonRpc20Encoder;
use RemoteObjects\Encode\RsaEncoder;
use ContaoManagementApi\Command\ConfigCommands;
use ContaoManagementApi\Command\LogsCommands;
use ContaoManagementApi\Command\StatusCommands;
use ContaoManagementApi\Command\SyslogCommands;
use ContaoManagementApi\Command\UserCommands;

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