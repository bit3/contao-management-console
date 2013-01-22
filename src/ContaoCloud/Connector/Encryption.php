<?php

namespace ContaoCloud\Connector;

class Encryption
{
	/**
	 * @var Settings
	 */
	protected $settings;

	/**
	 * @var Crypt_RSA
	 */
	protected $rsa = null;

	/**
	 * @var curl
	 */
	protected $curl = null;

	/**
	 * @param string $rsaPrivateKey
	 * @param string $rsaPublicKey
	 * @param bool $disableEncryption
	 * Disable the encryption. (for development purpose only!)
	 */
	public function __construct(Settings $settings)
	{
		$this->settings = $settings;
	}

	public function __destruct()
	{
		if ($this->curl !== null)
		{
			curl_close($this->curl);
		}
	}

	/**
	 * Encrypt the data block.
	 * Adding the session id to the url, if usin aes encryption.
	 *
	 * @param string $url
	 * @param mixed $data
	 */
	public function encrypt($data)
	{
		if (!$this->settings->isEncryptionEnabled()) {
			return $data;
		}

		// create the rsa encryption object
		if ($this->rsa === null) {
			$this->rsa = new Crypt_RSA();
		}

		// load the client public key for encryption
		$this->rsa->loadKey($this->settings->getRsaRemotePublicKey());

		// encrypt the data
		return $this->rsa->encrypt($data);
	}

	/**
	 * Decrypt the data block.
	 *
	 * @param mixed $data
	 */
	public function decrypt($data)
	{
		if (!$this->settings->isEncryptionEnabled()) {
			return $data;
		}

		// create the rsa encryption object
		if ($this->rsa === null) {
			$this->rsa = new Crypt_RSA();
		}

		// load the private key for decryption
		$this->rsa->loadKey($this->settings->getRsaLocalPrivateKey());

		// decrypt the response
		return $this->rsa->decrypt($data);
	}
}