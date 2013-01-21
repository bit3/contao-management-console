<?php

namespace ContaoCloud\Connector\Command;

interface CommandResponse {
	/**
	 * @return mixed
	 */
	public function data();

	/**
	 * Return list of errors
	 * @return array
	 */
	public function errors();
}
