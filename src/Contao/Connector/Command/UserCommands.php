<?php

namespace Contao\Connector\Command;

use PDO;
use Filicious\Filesystem;
use Filicious\Local\LocalAdapter;
use Contao\Connector\Settings;

class UserCommands extends AbstractCommands
{
	/**
	 * @param \Contao\Connector\Settings $settings
	 *
	 * @return UserInfoCommandResponse
	 */
	public function info()
	{
		$users  = array();
		$groups = array();

		if ($this->prepareDatabaseConnection()) {
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

		return (object) array(
			'users'  => (object) $users,
			'groups' => (object) $groups,
			'errors' => $this->errors
		);
	}

	public function passwordReset($userId, $password)
	{
		$contaoVersion = $this->getContaoVersion();

		$success = false;

		if (!$contaoVersion) {
			$this->errors[] = 'Could not detect Contao version!';
		}
		else if ($this->prepareDatabaseConnection()) {
			if (version_compare('3', $contaoVersion, '>=')) {

			}
			else if (version_compare('2.11', $contaoVersion, '>=')) {
				$stmt = $this->dbConnection->prepare('SELECT * FROM tl_user WHERE id=:id');
				$stmt->bindParam(':id', $userId, PDO::PARAM_INT);

				if ($stmt->execute() && $stmt->rowCount()) {
					$salt = substr(
						md5(uniqid(mt_rand(), true)),
						0,
						23
					);
					$hash = sha1($salt . $password) . ':' . $salt;

					$stmt = $this->dbConnection->prepare('UPDATE tl_user SET password=:hash WHERE id=:id');
					$stmt->bindParam(':hash', $hash);
					$stmt->bindParam(':id', $userId, PDO::PARAM_INT);

					if ($stmt->execute()) {
						$success = true;
					}
					else {
						$this->errors[] = 'Could not update user password!';
					}
				}
				else {
					$this->errors[] = sprintf(
						'The user id %s does not exists!',
						$userId
					);
				}
			}
			else {
				$this->errors[] = sprintf(
					'The contao version %s is not supported!',
					$contaoVersion
				);
			}
		}

		return (object) array(
			'success' => $success,
			'errors'  => $this->errors
		);
	}
}
