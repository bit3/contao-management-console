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

namespace ContaoManagementConsole\Endpoint\Command;

use PDO;
use ContaoManagementConsole\Settings;

class SyslogCommands extends AbstractCommands
{
	public function entries($filter = null)
	{
		$syslog = array();

		if ($this->prepareDatabaseConnection()) {
			$query = 'SELECT * FROM tl_log';
			$queryParams = array();

			$limit = 100;
			$offset = 0;

			if (!empty($filter)) {
				$whereParts = array();

				if (isset($filter->on)) {
					if (is_numeric($filter->on)) {
						$on = $filter->on;
					}
					else {
						$on = strtotime($filter->on);
					}

					// search for day $on +- 12 hours to handle different time zone settings
					$whereParts[] = 'tstamp >= :on-begin';
					$whereParts[] = 'tstamp <= :on-end';
					$queryParams[':on-begin'] = array($on - 43200, PDO::PARAM_INT);
					$queryParams[':on-end'] = array($on + 43200, PDO::PARAM_INT);
				}

				if (isset($filter->since)) {
					if (is_numeric($filter->since)) {
						$since = $filter->since;
					}
					else {
						$since = strtotime($filter->since);
					}

					$whereParts[] = 'tstamp >= :since';
					$queryParams[':since'] = array($since, PDO::PARAM_INT);
				}

				if (isset($filter->until)) {
					if (is_numeric($filter->until)) {
						$until = $filter->until;
					}
					else {
						$until = strtotime($filter->until);
					}

					$whereParts[] = 'tstamp <= :until';
					$queryParams[':until'] = array($until, PDO::PARAM_INT);
				}

				if (isset($filter->source)) {
					$whereParts[] = 'source = :source';
					$queryParams[':source'] = array(strtoupper($filter->source));
				}

				if (isset($filter->action)) {
					$whereParts[] = 'action = :action';
					$queryParams[':action'] = array(strtoupper($filter->action));
				}

				if (isset($filter->username)) {
					$whereParts[] = 'username = :username';
					$queryParams[':username'] = array(strtoupper($filter->username));
				}

				if (isset($filter->func)) {
					$whereParts[] = 'func = :func';
					$queryParams[':func'] = array(strtoupper($filter->func));
				}

				if (isset($filter->ip)) {
					$whereParts[] = 'ip = :ip';
					$queryParams[':ip'] = array(strtoupper($filter->ip));
				}

				if (count($whereParts)) {
					$query .= ' WHERE ' . implode(' AND ', $whereParts);
				}

				if (isset($filter->limit)) {
					$limit = (int) $filter->limit;
				}

				if (isset($filter->offset)) {
					$offset = (int) $filter->offset;
				}
			}

			$query .= ' LIMIT :offset,:limit';

			$stmt = $this->dbConnection->prepare($query);

			foreach ($queryParams as $name => $value) {
				$stmt->bindParam($name, $value[0], isset($value[1]) ? $value[1] : PDO::PARAM_STR);
			}
			$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
			$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

			$stmt->execute();

			$syslog = $stmt->fetchAll(PDO::FETCH_OBJ);
		}

		return (object) array(
			'syslog' => $syslog,
			'errors' => $this->errors
		);
	}
}
