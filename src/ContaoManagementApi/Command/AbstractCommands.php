<?php

namespace ContaoManagementApi\Command;

use Exception;
use PDO;
use Filicious\Filesystem;
use Filicious\Local\LocalAdapter;
use ContaoManagementApi\Settings;

abstract class AbstractCommands
{
	/**
	 * List of errors.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * @var Settings
	 */
	protected $settings;

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

	function __construct($settings)
	{
		$this->settings = $settings;
	}

	protected function prepareFilesystemAccess()
	{
		if ($this->contaoInstallation !== null) {
			return true;
		}

		if (!is_dir($this->settings->getPath())) {
			$this->errors[] = sprintf(
				'The path %s does not exists.',
				$this->settings->getPath()
			);
			return false;
		}

		$this->contaoInstallation = new Filesystem(new LocalAdapter($this->settings->getPath()));
		return true;
	}

	protected function prepareLocalconfig()
	{
		if ($this->localconfig !== null) {
			return true;
		}

		if ($this->prepareFilesystemAccess()) {
			$localconfigFile = $this->contaoInstallation->getFile('system/config/localconfig.php');
			if (!$localconfigFile->exists()) {
				$errors[] = sprintf(
					'system/config/localconfig.php is missing, maybe %s is not a contao installation or not configured yet?!',
					$this->settings->getPath()
				);
			}
			else {
				$this->localconfig = $localconfigFile->getContents();
				return true;
			}
		}

		return false;
	}

	protected function prepareDatabaseSettings()
	{
		if ($this->dbSettings !== null) {
			return true;
		}

		if ($this->prepareLocalconfig()) {
			/* Read db settings */
			$dbSettins = new \stdClass();

			$dbSettins->dbDriver   = $this->searchConfigEntry('dbDriver');
			$dbSettins->dbHost     = $this->searchConfigEntry('dbHost');
			$dbSettins->dbPort     = $this->searchConfigEntry('dbPort');
			$dbSettins->dbDatabase = $this->searchConfigEntry('dbDatabase');
			$dbSettins->dbCharset  = $this->searchConfigEntry('dbCharset');
			$dbSettins->dbUser     = $this->searchConfigEntry('dbUser');
			$dbSettins->dbPass     = $this->searchConfigEntry('dbPass');

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

	protected function prepareDatabaseConnection()
	{
		if ($this->dbConnection !== null) {
			return true;
		}

		if ($this->prepareDatabaseSettings()) {
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
			catch (Exception $e) {
				$this->errors[] = $e->getMessage();
			}
		}

		return false;
	}

	protected function searchConfigEntry($key)
	{
		if (preg_match(
			'#\$GLOBALS\[\'TL_CONFIG\'\]\[\'' . $key . '\'\]\s*=\s*(["\']([^\']*)["\']|([^"\']+));#',
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
			else {
				$value = $this->saveUnserialize($value);
			}

			return $value;
		}

		return null;
	}

	/**
	 * Search and replace a config variable
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $flush Flush changes to the file.
	 *
	 * @return array|bool|null
	 */
	protected function replaceConfigEntry($key, $value, $flush = false)
	{
		if (is_array($value) && !count($value)) {
			$value = '';
		}
		if (!is_scalar($value)) {
			$value = serialize($value);
		}

		$string = sprintf(
			'$GLOBALS[\'TL_CONFIG\'][\'%s\'] = %s;',
			$key,
			var_export($value, true)
		);

		if (preg_match(
			'#\$GLOBALS\[\'TL_CONFIG\'\]\[\'' . $key . '\'\]\s*=\s*(["\']([^\']*)["\']|([^"\']+));#',
			$this->localconfig,
			$match
		)
		) {
			$this->localconfig = str_replace(
				$match[0],
				$string,
				$this->localconfig
			);
		}
		else {
			$this->localconfig = str_replace(
				'### INSTALL SCRIPT STOP ###',
				$string . "\n### INSTALL SCRIPT STOP ###",
				$this->localconfig
			);
		}

		if ($flush) {
			$this->writeLocalconfigChanges();
		}

		return strpos($this->localconfig, $string) !== false;
	}

	/**
	 * Search and replace a config variable
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $flush Flush changes to the file.
	 *
	 * @return array|bool|null
	 */
	protected function removeConfigEntry($key, $flush = false)
	{
		if (preg_match(
			'#\$GLOBALS\[\'TL_CONFIG\'\]\[\'' . $key . '\'\]\s*=\s*(["\']([^\']*)["\']|([^"\']+));[\s\n\r]*#',
			$this->localconfig,
			$match
		)
		) {
			$this->localconfig = str_replace(
				$match[0],
				'',
				$this->localconfig
			);

			if ($flush) {
				$this->writeLocalconfigChanges();
			}

			return true;
		}

		return false;
	}

	protected function writeLocalconfigChanges()
	{
		if ($this->localconfig !== null) {
			$localconfigFile = $this->contaoInstallation->getFile('system/config/localconfig.php');
			$localconfigFile->setContents($this->localconfig, false);
		}
	}

	protected function getContaoVersion()
	{
		if ($this->prepareFilesystemAccess()) {
			/* ***** Read constants like VERSION, BUILD and LONG_TERM_SUPPORT ******************************************* */
			/* Contao 3+ */
			$constantsFile = $this->contaoInstallation->getFile('system/config/constants.php');
			/* Contao 2+ */
			if (!$constantsFile->exists()) {
				$constantsFile = $this->contaoInstallation->getFile('system/constants.php');
			}

			if ($constantsFile->exists()) {
				$constants = $constantsFile->getContents();

				$version = '';
				$build   = '';

				if (preg_match('#define\(\s*["\']VERSION["\']\s*,\s*["\']([^"\']+)["\']\s*\)#', $constants, $match)) {
					$version = $match[1];
				}
				if (preg_match('#define\(\s*["\']BUILD["\']\s*,\s*["\']([^"\']+)["\']\s*\)#', $constants, $match)) {
					$build = $match[1];
				}

				if ($version && $build) {
					return $version . '.' . $build;
				}
			}
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
