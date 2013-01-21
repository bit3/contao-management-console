<?php

namespace ContaoCloud\Connector\Command;

class StatusResponseCommand implements ResponseCommand
{
	protected $status;

	protected $errors;

	function __construct($status, array $errors)
	{
		$this->status = $status;
		$this->errors = $errors;
	}

	public function data()
	{
		return $this->status;
	}

	public function errors()
	{
		return $this->errors;
	}
}
