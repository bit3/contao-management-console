<?php

use ContaoCloud\Connector\Settings;
use ContaoCloud\Connector\Encryption;
use ContaoCloud\Connector\CommandRequestFactory;

class connector
{
	public static function getInstance()
	{
		return new connector();
	}

	public function run()
	{
		// change into parent directory
		$path = dirname(__FILE__);
		$parentPath = dirname($path);
		chdir($parentPath);

		$settings = new Settings();

		if (defined('CLOUD_CONTAO_PATH')) {
			$settings->setPath(CLOUD_CONTAO_PATH);
		}
		if (defined('CLOUD_RSA_LOCAL_PRIVATE_KEY')) {
			$settings->setRsaLocalPrivateKey(CLOUD_RSA_LOCAL_PRIVATE_KEY);
		}
		if (defined('CLOUD_RSA_REMOTE_PUBLIC_KEY')) {
			$settings->setRsaRemotePublicKey(CLOUD_RSA_REMOTE_PUBLIC_KEY);
		}

		// get request data
		$request = file_get_contents('php://input');

		$encryption = new Encryption($settings);

		// decrypt request data
		try {
			$request = $encryption->decrypt($request);
		} catch(Exception $e) {
			header("HTTP/1.0 403 Forbidden");
			exit;
		}

		// decode the request
		$request = json_decode($request);

		if (!is_object($request) || !isset($request->command)) {
			header("HTTP/1.0 406 Not Acceptable");
			exit;
		}

		$factory = new CommandRequestFactory();
		$commandRequest = $factory->create($settings, $request->command, $request->config);
		$commandResponse = $commandRequest->execute($settings);

		$response = serialize($commandResponse);

		// encrypt response
		$response = $encryption->encrypt($response);

		while(ob_end_clean());

		header('Content-Type: application/octet-stream');
		echo $response;
		exit;
	}
}

connector::getInstance()->run();
