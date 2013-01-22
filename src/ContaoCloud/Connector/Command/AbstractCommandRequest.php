<?php

namespace ContaoCloud\Connector\Command;

use Exception;
use PDO;
use Filicious\Filesystem;
use Filicious\Local\LocalAdapter;
use ContaoCloud\Connector\Settings;

abstract class AbstractCommandRequest implements CommandRequest {
	/**
	 * List of errors.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * The filesystem to the contao installation path.
	 *
	 * @var \Filicious\Filesystem
	 */
	protected $contaoInstallation = null;

	/**
	 * The content of the localconfig file.
	 *
	 * @var string|null
	 */
	protected $localconfig = null;

	/**
	 * An object containing the database connection settings.
	 *
	 * @var stdClass|null
	 */
	protected $dbSettings = null;

	/**
	 * Database connection object.
	 *
	 * @var PDO|null
	 */
	protected $dbConnection = null;

	protected function prepareFilesystemAccess(Settings $settings)
	{
		if ($this->contaoInstallation !== null) {
			return true;
		}

		if (!is_dir($settings->getPath())) {
			$this->errors[] = sprintf(
				'The path %s does not exists.',
				$settings->getPath()
			);
			return false;
		}

		$this->contaoInstallation = new Filesystem(new LocalAdapter($settings->getPath()));
		return true;
	}

	protected function prepareLocalconfig(Settings $settings)
	{
		if ($this->localconfig !== null) {
			return true;
		}

		if ($this->prepareFilesystemAccess($settings)) {
			$localconfigFile = $this->contaoInstallation->getFile('system/config/localconfig.php');
			if (!$localconfigFile->exists()) {
				$errors[] = sprintf(
					'system/config/localconfig.php is missing, maybe %s is not a contao installation or not configured yet?!',
					$settings->getPath()
				);
			}
			else {
				$this->localconfig = $localconfigFile->getContents();
				return true;
			}
		}

		return false;
	}

	protected function prepareDatabaseSettings(Settings $settings)
	{
		if ($this->dbSettings !== null) {
			return true;
		}

		if ($this->prepareLocalconfig($settings)) {
			/* Read db settings */
			$dbSettins = new \stdClass();

			$dbSettins->dbDriver = $this->searchConfigEntry('dbDriver');
			$dbSettins->dbHost = $this->searchConfigEntry('dbHost');
			$dbSettins->dbPort = $this->searchConfigEntry('dbPort');
			$dbSettins->dbDatabase = $this->searchConfigEntry('dbDatabase');
			$dbSettins->dbCharset = $this->searchConfigEntry('dbCharset');
			$dbSettins->dbUser = $this->searchConfigEntry('dbUser');
			$dbSettins->dbPass = $this->searchConfigEntry('dbPass');

			if (!isset($dbSettins->dbDriver) || !isset($dbSettins->dbUser) || !isset($dbSettins->dbDatabase)) {
				$errors[] = 'system/config/localconfig.php does not contains a suitable database setup!';
			}
			else if (stripos(strtolower($dbSettins->dbDriver), 'mysql') === false) {
				$errors[] = 'Only mysql database is supported!';
			}
			else {
				$this->dbSettings = $dbSettins;
				return true;
			}
		}
		return false;
	}

	protected function prepareDatabaseConnection(Settings $settings)
	{
		if ($this->dbConnection !== null) {
			return true;
		}

		if ($this->prepareDatabaseSettings($settings)) {
			$username = $this->dbSettings->dbUser;
			$password = isset($this->dbSettings->dbPass) ? $this->dbSettings->dbPass : '';

			$dsn     = 'mysql:';
			$options = array();

			if (isset($this->dbSettings->dbHost)) {
				$dsn .= 'host=' . $this->dbSettings->dbHost;
			}
			else {
				$dsn .= 'host=localhost';
			}

			if (isset($this->dbSettings->dbPort)) {
				$dsn .= ';port=' . $this->dbSettings->dbPort;
			}

			$dsn .= ';dbname=' . $this->dbSettings->dbDatabase;

			if (isset($this->dbSettings->dbCharset)) {
				$options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $this->dbSettings->dbCharset;
			}

			$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

			try {
				$this->dbConnection = new PDO(
					$dsn,
					$username,
					$password,
					$options
				);
				return true;
			}
			catch(Exception $e) {
				$this->errors[] = $e->getMessage();
			}
		}

		return false;
	}

	protected function searchConfigEntry($key)
	{
		if (preg_match(
			'#\$GLOBALS\[\'TL_CONFIG\'\]\[\'' . $key . '\'\]\s*=\s*(["\']([^\']+)["\']|([^"\']+));#',
			$this->localconfig,
			$match
		)
		) {
			$value = isset($match[3]) ? $match[3] : $match[2];

			if ($value == 'true') {
				$value = true;
			}
			else if ($value == 'false') {
				$value = false;
			}

			return $value;
		}

		return null;
	}

	protected function saveUnserializeKeys(&$object)
	{
		foreach ($object as $key => $value) {
			$temp = @unserialize($value);

			if (is_array($temp)) {
				$object->$key = $temp;
			}
		}
	}

	protected function saveUnserialize($value)
	{
		$temp = @unserialize($value);

		if (is_array($temp)) {
			return $temp;
		}

		return $value;
	}
}
