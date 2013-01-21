<?php

namespace ContaoCloud\Connector\Command;

use ContaoCloud\Connector\Settings;

interface RequestCommand {
	/**
	 * @param $request
	 *
	 * @return RequestCommand
	 */
	public static function create($config);

	/**
	 * @param \ContaoCloud\Connector\Settings $settings
	 *
	 * @return ResponseCommand
	 */
	public function execute(Settings $settings);
}
