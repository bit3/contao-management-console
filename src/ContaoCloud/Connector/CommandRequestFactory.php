<?php

namespace ContaoCloud\Connector;

use ContaoCloud\Connector\Command\RemoteCommandRequest;

class CommandRequestFactory {
	protected $logger;

	public function create(Settings $settings, $command, $config) {
		$url = parse_url($settings->getPath());

		// lokal call
		if (empty($url['scheme']) || $url['scheme'] == 'file') {
			$parts = explode(':', $command);
			$parts = array_map('ucfirst', $parts);

			$class = 'ContaoCloud\\Connector\\Command\\' . implode('', $parts) . 'CommandRequest';

			$request = $class::create($config);
		}

		// remote call (use a proxy)
		else {
			$request = RemoteCommandRequest::create($command, $config);
		}

		return $request;
	}
}