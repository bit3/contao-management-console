<?php

namespace Contao\Connector;

use RemoteObjects\Client;
use RemoteObjects\Transport\CurlClient;
use RemoteObjects\Encode\JsonRpc20Encoder;
use RemoteObjects\Encode\RsaEncoder;
use Contao\Connector\Command\StatusCommands;
use Contao\Connector\Command\UserCommands;
use Contao\Connector\Command\SyslogCommands;

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
			$endpoint->status = new StatusCommands($settings);
			$endpoint->user   = new UserCommands($settings);
			$endpoint->syslog = new SyslogCommands($settings);
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