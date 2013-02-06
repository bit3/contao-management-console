<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use RemoteObjects\Server;
use RemoteObjects\Transport\HttpServer;
use RemoteObjects\Encode\JsonRpc20Encoder;
use RemoteObjects\Encode\RsaEncoder;
use ContaoManagementApi\Settings;
use ContaoManagementApi\Encryption;
use ContaoManagementApi\EndpointFactory;

class connector
{
	public static function getInstance()
	{
		return new connector();
	}

	public function run()
	{
		ob_start();
		error_reporting(E_ALL ^ E_NOTICE);

		$logger = new Logger('contao-connector');
		$logger->pushHandler(
			new StreamHandler(
				CONTAO_CONNECTOR_LOG,
				CONTAO_CONNECTOR_LOG_LEVEL
			)
		);

		// change into parent directory
		$path       = dirname(__FILE__);
		$parentPath = dirname($path);
		chdir($parentPath);

		$settings = new Settings();

		if (defined('CONTAO_CONNECTOR_CONTAO_PATH')) {
			$settings->setPath(CONTAO_CONNECTOR_CONTAO_PATH);
		}
		if (defined('CONTAO_CONNECTOR_RSA_LOCAL_PRIVATE_KEY')) {
			$settings->setRsaLocalPrivateKey(CONTAO_CONNECTOR_RSA_LOCAL_PRIVATE_KEY);
		}
		if (defined('CONTAO_CONNECTOR_RSA_REMOTE_PUBLIC_KEY')) {
			$settings->setRsaRemotePublicKey(CONTAO_CONNECTOR_RSA_REMOTE_PUBLIC_KEY);
		}

		$factory  = new EndpointFactory();
		$endpoint = $factory->createEndpoint($settings);

		$transport = new HttpServer('application/json');
		$transport->setLogger($logger);

		$encoder = new JsonRpc20Encoder();
		$encoder->setLogger($logger);

		if ($settings->isEncryptionEnabled()) {
			$encoder = new RsaEncoder(
				$encoder,
				$settings->getRsaRemotePublicKey(),
				$settings->getRsaLocalPrivateKey()
			);
		}

		$server = new Server(
			$transport,
			$encoder,
			$endpoint
		);

		try {
			$server->handle();
		}
		catch (Exception $e) {
			if ($logger->isHandling(Logger::ERROR)) {
				$logger->addError(
					$e->getMessage()
				);
			}
			ob_start();
			while (ob_end_clean()) {
			}
			header('HTTP/1.0 500 Internal Server Error');
			header("Status: 500 Internal Server Error");
			header('Content-Type: text/plain; charset=utf-8');
			echo '500 Internal Server Error';
		}
		exit;
	}
}

connector::getInstance()
	->run();
