<?php

class CloudServer extends ConnectionEncryption
{
	public function __construct($rsaPrivateKey, $rsaPublicKey, $disableEncryption = false)
	{
		parent::__construct($rsaPrivateKey, $rsaPublicKey, $disableEncryption);

		/*
		if (apc_exists('server')) {
			$store = apc_fetch('server');
			$this->sessionID = $store->sessionID;
			$this->aesKey    = $store->aesKey;
		}
		*/
	}

	public function doHandshake($strUrl, $blnRetry = true)
	{
		// create a new session id
		$sessionID = md5(md5(mt_rand()) ^ md5(mt_rand()) ^ md5(mt_rand()));

		// create a command
		$command = new stdClass();
		$command->do = 'handshake';
		$command->sessionID = $sessionID;

		// send the command
		try {
			$response = $this->sendCommand($strUrl, $command);
		} catch(Exception $e) {
			if ($e->getCode() == 400 && $blnRetry) {
				apc_delete('server');
				$this->sessionID = null;
				$this->aesKey    = null;
				return $this->doHandshake($strUrl, false);
			}
			throw $e;
		}

		// if request failed...
		if (!$response || $sessionID != $response->sessionID) {
			// ...return false
			return false;
		}

		$this->sessionID = $sessionID;
		$this->aesKey    = $response->aesKey;

		apc_store('server', $response, 3600);

		return true;
	}

	public function sendCommand($strUrl, $varCommand)
	{
		// encode the command
		$request = json_encode($varCommand);

		// encrypt the command
		$this->encrypt($request, $strUrl);

		// if using aes encryption, add the session id
		if ($this->sessionID && $this->aesKey) {
			// add session id to url
			$strUrl .= (strpos($strUrl, '?') !== false ? '&' : '?') . '__session__=' . $this->sessionID;
		}

		// do the request
		$response = $this->doRequest($strUrl, $request);

		// if request failed...
		if (!$response) {
			// ...return false
			return false;
		}

		// decrypt the command
		$this->decrypt($response);

		// decode the response
		$response = json_decode($response);

		return $response;
	}
}
