<?php

namespace ContaoCloud\Connector\Command;

use Exception;
use ContaoCloud\Connector\Encryption;
use ContaoCloud\Connector\Settings;

class RemoteCommandRequest extends AbstractCommandRequest
{
	public static function create($command, $config = null)
	{
		return new RemoteCommandRequest($command, $config);
	}

	/**
	 * @var string
	 */
	protected $command;

	/**
	 * @var mixed
	 */
	protected $config;

	/**
	 * @var resource
	 */
	protected $curl;

	protected function __construct($command, $config)
	{
		$this->command = $command;
		$this->config = $config;
	}

	/**
	 * @param \ContaoCloud\Connector\Settings $settings
	 *
	 * @return CommandResponse
	 * @throws Exception
	 */
	public function execute(Settings $settings)
	{
		$url = $settings->getPath();

		// if there is no curl instance yet...
		if ($this->curl === null)
		{
			// ...create one
			$this->curl = curl_init();

			// set transfer to binary (for encrypted data)
			curl_setopt($this->curl, CURLOPT_BINARYTRANSFER, true);

			// set method to POST
			curl_setopt($this->curl, CURLOPT_POST, true);
		}

		$encryption = new Encryption($settings);

		// build request data
		$data = (object) array(
			'command' => $this->command,
			'config'  => $this->config
		);
		$data = json_encode($data);
		$data = $encryption->encrypt($data);

		// create a temporary file, to store the response in it
		$headerStream = tmpfile();
		$responseStream = tmpfile();

		// set the request url
		curl_setopt($this->curl, CURLOPT_URL, $url);

		// set the request body
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

		// set the headers output file
		curl_setopt($this->curl, CURLOPT_WRITEHEADER, $headerStream);

		// set the response output file
		curl_setopt($this->curl, CURLOPT_FILE, $responseStream);

		// exec request
		if (curl_exec($this->curl)) {
			// read status code
			$httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

			// read response from temporary file
			rewind($responseStream);
			$response = stream_get_contents($responseStream);

			// if request not success...
			if ($httpCode != 200) {
				// ...throw an exception
				throw new Exception('Got HTTP status ' . $httpCode . ' for ' . $url . '! ' . $response, $httpCode);
			}

			// close temporary files
			fclose($headerStream);
			fclose($responseStream);

			return unserialize($response);
		}

		// request failed
		else {
			// close temporary files
			fclose($headerStream);
			fclose($responseStream);

			throw new Exception('Could not fetch ' . $url . '!');
		}
	}
}
