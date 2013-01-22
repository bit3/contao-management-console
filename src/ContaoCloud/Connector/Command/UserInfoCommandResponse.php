<?php

namespace ContaoCloud\Connector\Command;

class UserInfoCommandResponse implements CommandResponse
{
	/**
	 * @var array
	 */
	protected $users;

	/**
	 * @var array
	 */
	protected $groups;

	protected $errors;

	function __construct(array $users, array $groups, array $errors)
	{
		$this->users  = $users;
		$this->groups = $groups;
		$this->errors = $errors;
	}

	/**
	 * @return array
	 */
	public function getUsers()
	{
		return $this->users;
	}

	/**
	 * @return array
	 */
	public function getGroups()
	{
		return $this->groups;
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
