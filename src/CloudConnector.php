<?php

class CloudConnector extends ConnectionEncryption
{
	public function __construct($rsaPrivateKey, $rsaPublicKey, $disableEncryption = false)
	{
		parent::__construct($rsaPrivateKey, $rsaPublicKey, $disableEncryption);

		if (isset($_GET['__session__']))
		{
			$sessionID = preg_replace('#[^a-f0-9]#', '', $_GET['__session__']);

			if (apc_exists('client:' . $sessionID)) {
				$store = apc_fetch('client:' . $sessionID);

				// session store is invalid or outdated
				$this->sessionID = $sessionID;
				$this->aesKey    = $store->aesKey;
			}
			else {
				header("HTTP/1.0 400 Bad Request");
				exit;
			}
		}
	}

	/**
	 * Handle a request.
	 */
	public function handleRequest()
	{
		// get request data
		$request = file_get_contents('php://input');

		// decrypt request data
		try {
			$this->decrypt($request);
		} catch(Exception $e) {
			header("HTTP/1.0 403 Forbidden");
			exit;
		}

		// decode the request
		$command = json_decode($request);

		if (!is_object($command) || !isset($command->do)) {
			header("HTTP/1.0 406 Not Acceptable");
			exit;
		}

		switch ($command->do)
		{
			case 'handshake':
				$this->handleHandshake($command);
				break;

			case 'hello':
				$this->handleHello($command);
				break;

			default:
				header("HTTP/1.0 405 Method Not Allowed");
				exit;
		}
	}

	protected function sendResponse($response)
	{
		// encode the response
		$response = json_encode($response);

		// encrypt the response
		$this->encrypt($response);

		// output response and exit
		echo $response;
		exit;
	}

	protected function handleHandshake($command)
	{
		// generate a aes key
		$aesKey = md5(md5(mt_rand()) ^ md5(mt_rand()) ^ md5(mt_rand()));

		// create a store object to hold the data
		$store = new stdClass();
		$store->sessionID = $command->sessionID;
		$store->aesKey    = $aesKey;
		$store->time      = time();

		// store in apc by session id
		apc_store('client:' . $command->sessionID, $store, 3600);

		// send the response
		$this->sendResponse($store);
	}

	protected function handleHello($command)
	{
		$response = 'Nice to meet you!';

		// send the response
		$this->sendResponse($response);
	}
}
