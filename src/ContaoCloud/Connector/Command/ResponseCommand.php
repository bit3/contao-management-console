<?php

namespace ContaoCloud\Connector\Command;

interface ResponseCommand {
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
