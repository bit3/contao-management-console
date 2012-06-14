<?php

abstract class ConnectionEncryption
{
	/**
	 * @var string
	 */
	protected $rsaPrivateKey = null;

	/**
	 * @var string
	 */
	protected $rsaPublicKey = null;

	/**
	 * @var Crypt_RSA
	 */
	protected $rsa = null;

	/**
	 * @var string
	 */
	protected $sessionID = null;

	/**
	 * @var Crypt_AES
	 */
	protected $aes = null;

	/**
	 * @var string
	 */
	protected $aesKey = null;

	/**
	 * @var curl
	 */
	protected $curl = null;

	/**
	 * @var bool
	 */
	protected $disableEncryption = false;

	/**
	 * @param string $rsaPrivateKey
	 * @param string $rsaPublicKey
	 * @param bool $disableEncryption
	 * Disable the encryption. (for development purpose only!)
	 */
	public function __construct($rsaPrivateKey, $rsaPublicKey, $disableEncryption = false)
	{
		$this->rsaPrivateKey = $rsaPrivateKey;
		$this->rsaPublicKey = $rsaPublicKey;
		$this->disableEncryption = $disableEncryption;

		// create the rsa encryption object
		$this->rsa = new Crypt_RSA();

		// create the aes encryption object
		$this->aes = new Crypt_AES();
	}

	public function __destruct()
	{
		if ($this->curl !== null)
		{
			curl_close($this->curl);
		}
	}

	protected function doRequest($strUrl, $strBody)
	{
		// if there is no curl instance yet...
		if ($this->curl === null)
		{
			// ...create one
			$this->curl = curl_init();

			// set transfer to binary (for encrypted data)
			curl_setopt($this->curl, CURLOPT_BINARYTRANSFER, true);

			// set method to POST
			curl_setopt($this->curl, CURLOPT_POST, true);

			// say curl to return the transfered data
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		}

		// create a temporary file, to store the response in it
		$headerStream = tmpfile();
		$responseStream = tmpfile();

		// set the request url
		curl_setopt($this->curl, CURLOPT_URL, $strUrl);

		// set the request body
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $strBody);

		// set the headers output file
		curl_setopt($this->curl, CURLOPT_WRITEHEADER, $headerStream);

		// set the response output file
		curl_setopt($this->curl, CURLOPT_FILE, $responseStream);

		// exec request
		if ($foo = curl_exec($this->curl)) {
			// read status code
			$httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

			// read response from temporary file
			rewind($responseStream);
			$response = stream_get_contents($responseStream);

			// if request not success...
			if ($httpCode != 200) {
				// ...throw an exception
				throw new Exception('Got HTTP status ' . $httpCode . ' for ' . $strUrl . '! ' . $response, $httpCode);
			}

			// close temporary files
			fclose($headerStream);
			fclose($responseStream);

			return $response;
		}

		// request failed
		else {
			// close temporary files
			fclose($headerStream);
			fclose($responseStream);

			return false;
		}
	}

	/**
	 * Encrypt the data block.
	 * Adding the session id to the url, if usin aes encryption.
	 *
	 * @param string $url
	 * @param mixed $data
	 */
	protected function encrypt(&$data)
	{
		if ($this->disableEncryption) {
			return;
		}

		if ($this->sessionID && $this->aesKey) {
			// load the aes key
			$this->aes->setKey($this->aesKey);

			// encrypt the command
			$data = $this->aes->encrypt($data);
		}
		else {
			// load the client public key for encryption
			$this->rsa->loadKey($this->rsaPublicKey);

			// encrypt the command
			$data = $this->rsa->encrypt($data);
		}
	}

	/**
	 * Decrypt the data block.
	 *
	 * @param mixed $data
	 */
	protected function decrypt(&$data)
	{
		if ($this->disableEncryption) {
			return;
		}

		if ($this->sessionID && $this->aesKey) {
			// load the aes key
			$this->aes->setKey($this->aesKey);

			// decrypt the response
			$data = $this->aes->decrypt($data);
		}
		else {
			// load the private key for decryption
			$this->rsa->loadKey($this->rsaPrivateKey);

			// decrypt the response
			$data = $this->rsa->decrypt($data);
		}
	}

	/**
	 * @return string
	 */
	public function getAesKey()
	{
		return $this->aesKey;
	}

	/**
	 * @return string
	 */
	public function getRsaPrivateKey()
	{
		return $this->rsaPrivateKey;
	}

	/**
	 * @return string
	 */
	public function getRsaPublicKey()
	{
		return $this->rsaPublicKey;
	}

	/**
	 * @return string
	 */
	public function getSessionID()
	{
		return $this->sessionID;
	}
}