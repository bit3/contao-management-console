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

class connect
{
	public static function getInstance()
	{
		return new connect();
	}

	public function run()
	{
		ob_start();
		error_reporting(E_ALL ^ E_NOTICE);

		if (defined('CONTAO_MANAGEMENT_API_LOG')) {
			$log = 'contao-management-api';
			if (defined('CONTAO_MANAGEMENT_API_LOG_NAME')) {
				$log = CONTAO_MANAGEMENT_API_LOG_NAME;
			}

			$logger = new Logger($log);
			$logger->pushHandler(
				new StreamHandler(
					CONTAO_MANAGEMENT_API_LOG,
					CONTAO_MANAGEMENT_API_LOG_LEVEL
				)
			);
		}
		else {
			$logger = null;
		}

		// change into parent directory
		$path       = dirname(__FILE__);
		$parentPath = dirname($path);
		chdir($parentPath);

		$settings = new Settings();

		if (defined('CONTAO_MANAGEMENT_API_CONTAO_PATH')) {
			$settings->setPath(CONTAO_MANAGEMENT_API_CONTAO_PATH);
		}
		if (defined('CONTAO_MANAGEMENT_API_RSA_LOCAL_PRIVATE_KEY')) {
			$settings->setRsaLocalPrivateKey(CONTAO_MANAGEMENT_API_RSA_LOCAL_PRIVATE_KEY);
		}
		if (defined('CONTAO_MANAGEMENT_API_RSA_REMOTE_PUBLIC_KEY')) {
			$settings->setRsaRemotePublicKey(CONTAO_MANAGEMENT_API_RSA_REMOTE_PUBLIC_KEY);
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
			if ($logger && $logger->isHandling(Logger::ERROR)) {
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

connect::getInstance()
	->run();