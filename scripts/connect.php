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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use RemoteObjects\Server;
use RemoteObjects\Transport\HttpServer;
use RemoteObjects\Encode\JsonRpc20Encoder;
use RemoteObjects\Encode\RsaEncoder;
use ContaoManagementConsole\Settings;
use ContaoManagementConsole\EndpointFactory;

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

		if (defined('COMACO_LOG')) {
			$log = 'contao-management-api';
			if (defined('COMACO_LOG_NAME')) {
				$log = COMACO_LOG_NAME;
			}

			$logger = new Logger($log);
			$logger->pushHandler(
				new StreamHandler(
					COMACO_LOG,
					COMACO_LOG_LEVEL
				)
			);
		}
		else {
			$logger = null;
		}

		if (isset($_GET['ping'])) {
			header('Content-Type: text/plain; charset=utf-8');
			echo 'pong';
			exit;
		}

		$settings = new Settings();

		if (defined('COMACO_CONTAO_PATH')) {
			$settings->setPath(COMACO_CONTAO_PATH);
		}
		if (defined('COMACO_RSA_LOCAL_PRIVATE_KEY')) {
			$settings->setRsaLocalPrivateKey(COMACO_RSA_LOCAL_PRIVATE_KEY);
		}
		if (defined('COMACO_RSA_REMOTE_PUBLIC_KEY')) {
			$settings->setRsaRemotePublicKey(COMACO_RSA_REMOTE_PUBLIC_KEY);
		}

		$factory  = new EndpointFactory();
		$endpoint = $factory->createEndpoint($settings);

		$transport = new HttpServer('application/json');
		if ($logger) {
			$transport->setLogger($logger);
		}

		$encoder = new JsonRpc20Encoder();
		if ($logger) {
			$encoder->setLogger($logger);
		}

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
