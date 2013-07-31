<?php

/**
 * Management Console for Contao Open Source CMS
 *
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    contao-management-console
 * @license    LGPL-3.0+
 * @filesource
 */

namespace ContaoManagementApi\Command;

use PDO;
use Filicious\Filesystem;
use Filicious\Local\LocalAdapter;
use ContaoManagementApi\Settings;

class UserCommands extends AbstractCommands
{
	/**
	 * @param \ContaoManagementApi\Settings $settings
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

	public function resetPassword($userIdentifier, $password)
	{
		$contaoVersion = $this->getContaoVersion();

		$success = false;

		if (!$contaoVersion) {
			$this->errors[] = 'Could not detect Contao version!';
		}
		else if ($this->prepareDatabaseConnection()) {
				$stmt = $this->dbConnection->prepare(
					'SELECT id, username FROM tl_user WHERE id=:identifier OR username=:identifier OR email=:identifier'
				);
				$stmt->bindParam(':identifier', $userIdentifier);

			if (!$stmt->execute() || !$stmt->rowCount()) {
				$this->errors[] = sprintf(
					'The user id %s does not exists!',
					$userIdentifier
				);
			}
			else {
				$userId = $stmt->fetchColumn(0);

				$hash = null;

				if (version_compare($contaoVersion, '3', '>=')) {
					if (CRYPT_SHA512 == 1)
					{
						$hash = crypt($password, '$6$' . md5(uniqid(mt_rand(), true)) . '$');
					}
					elseif (CRYPT_SHA256 == 1)
					{
						$hash = crypt($password, '$5$' . md5(uniqid(mt_rand(), true)) . '$');
					}
					elseif (CRYPT_BLOWFISH == 1)
					{
						$hash = crypt($password, '$2a$07$' . md5(uniqid(mt_rand(), true)) . '$');
					}
					else
					{
						$this->errors[] = 'Security violation: none of the required crypt() algorithms is available';
					}
				}
				else if (version_compare($contaoVersion, '2.11', '>=')) {
						$salt = substr(
							md5(uniqid(mt_rand(), true)),
							0,
							23
						);
						$hash = sha1($salt . $password) . ':' . $salt;
				}
				else {
					$this->errors[] = sprintf(
						'The contao version %s is not supported!',
						$contaoVersion
					);
				}

				if ($hash) {
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
			}
		}

		return (object) array(
			'success' => $success,
			'errors'  => $this->errors
		);
	}
}
