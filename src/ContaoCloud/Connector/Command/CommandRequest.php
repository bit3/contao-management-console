<?php

namespace ContaoCloud\Connector\Command;

use ContaoCloud\Connector\Settings;

interface CommandRequest {
	/**
	 * @param $request
	 *
	 * @return CommandRequest
	 */
	public static function create($config);

	/**
	 * @param \ContaoCloud\Connector\Settings $settings
	 *
	 * @return CommandResponse
	 */
	public function execute(Settings $settings);
}
