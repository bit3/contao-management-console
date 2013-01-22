<?php

namespace ContaoCloud\Connector\Command;

use PDO;
use Filicious\Filesystem;
use Filicious\Local\LocalAdapter;
use ContaoCloud\Connector\Settings;

class UserInfoCommandRequest extends AbstractCommandRequest
{
	/**
	 * @param $config
	 *
	 * @return UserInfoCommandRequest
	 */
	public static function create($config)
	{
		return new UserInfoCommandRequest($config);
	}

	protected function __construct($config)
	{
	}

	/**
	 * @param \ContaoCloud\Connector\Settings $settings
	 *
	 * @return UserInfoCommandResponse
	 */
	public function execute(Settings $settings)
	{
		$errors = array();
		$users = array();
		$groups = array();

		if ($this->prepareDatabaseConnection($settings)) {
			/* Read users */
			$tablesStatement = $this->dbConnection->query('SHOW TABLES LIKE "tl_user";', PDO::FETCH_NUM);
			if (!$tablesStatement->rowCount()) {
				$errors[] = 'The table tl_user does not exists, could not find users!';
			}
			else {
				$rs = $this->dbConnection->query(
					'SELECT * FROM tl_user ORDER BY username',
					PDO::FETCH_OBJ
				);
				foreach ($rs as $user) {
					$this->saveUnserializeKeys($user);
					$users[$user->id] = $user;
				}
			}

			/* Read groups */
			$tablesStatement = $this->dbConnection->query('SHOW TABLES LIKE "tl_user_group";', PDO::FETCH_NUM);
			if (!$tablesStatement->rowCount()) {
				$errors[] = 'The table tl_user_group does not exists, could not find groups!';
			}
			else {
				$rs = $this->dbConnection->query(
					'SELECT * FROM tl_user_group ORDER BY name',
					PDO::FETCH_OBJ
				);
				foreach ($rs as $group) {
					$this->saveUnserializeKeys($group);
					$groups[$group->id] = $group;
				}
			}
		}

		return new UserInfoCommandResponse($users, $groups, $errors);
	}
}
