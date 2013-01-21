<?php

namespace ContaoCloud\Connector\Command;

class StatusCommandResponse implements CommandResponse
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
