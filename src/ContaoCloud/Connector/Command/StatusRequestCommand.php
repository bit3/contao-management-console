<?php

namespace ContaoCloud\Connector\Command;

use PDO;
use Filicious\Filesystem;
use Filicious\Local\LocalAdapter;
use ContaoCloud\Connector\Settings;

class StatusRequestCommand implements RequestCommand
{
	public static function create($config)
	{
		return new StatusRequestCommand($config);
	}

	protected function __construct($config)
	{
	}

	public function execute(Settings $settings)
	{
		$errors = array();
		$status = (object) array(
			'version'            => -1,
			'build'              => -1,
			'lts'                => false,
			'modules'            => array(),
			'disabledModules'    => array(),
			'extensions'         => array(),
			'users'              => array()
		);

		$fs = new Filesystem(new LocalAdapter($settings->getPath()));

		/* ***** Read constants like VERSION, BUILD and LONG_TERM_SUPPORT ******************************************* */
		/* Contao 3+ */
		$constantsFile = $fs->getFile('system/config/constants.php');
		/* Contao 2+ */
		if (!$constantsFile->exists()) {
			$constantsFile = $fs->getFile('system/constants.php');
		}

		if (!$constantsFile->exists()) {
			$errors[] = sprintf(
				'system/[config/]constants.php is missing, maybe %s is not a contao installation?!',
				$settings->getPath()
			);
		}
		else {
			$constants = $constantsFile->getContents();

			if (preg_match('#define\(\s*["\']VERSION["\']\s*,\s*["\']([^"\']+)["\']\s*\)#', $constants, $match)) {
				$status->version = $match[1];
			}
			if (preg_match('#define\(\s*["\']BUILD["\']\s*,\s*["\']([^"\']+)["\']\s*\)#', $constants, $match)) {
				$status->build = $match[1];
			}
			if (preg_match('#define\(\s*["\']LONG_TERM_SUPPORT["\']\s*,\s*(true|false)\s*\)#', $constants, $match)) {
				$status->lts = $match[1] == 'true';
			}
		}

		/* ***** Read installed extensions ************************************************************************** */
		$modulesDir = $fs->getFile('system/modules');
		if (!$modulesDir->isDirectory()) {
			$errors[] = sprintf(
				'system/modules/ is missing, maybe %s is not a contao installation?!',
				$settings->getPath()
			);
		}
		else {
			/** @var \Filicious\File $moduleDir */
			foreach ($modulesDir as $moduleDir) {
				$name = $moduleDir->getBasename();
				if ($name != 'backend' && $name != 'frontend' && $name != 'core') {
					$status->modules[] = $name;
				}
			}
			natcasesort($status->modules);
		}

		/* ***** Read localconfig *********************************************************************************** */
		$localconfigFile = $fs->getFile('system/config/localconfig.php');
		if (!$localconfigFile->exists()) {
			$errors[] = sprintf(
				'system/config/localconfig.php is missing, maybe %s is not a contao installation or not configured yet?!',
				$settings->getPath()
			);
		}
		else {
			$localconfig = $localconfigFile->getContents();

			/* Read incompatible versions */
			if (preg_match(
				'#\$GLOBALS\[\'TL_CONFIG\'\]\[\'inactiveModules\'\]\s*=\s*\'([^\']+)\';#',
				$localconfig,
				$match
			)
			) {
				$status->disabledModules = unserialize($match[1]);
				natcasesort($status->disabledModules);
			}

			/* Read db settings */
			$dbSettins = new \stdClass();

			foreach (array('dbDriver', 'dbHost', 'dbPort', 'dbDatabase', 'dbCharset', 'dbUser', 'dbPass') as $key) {
				if (preg_match(
					'#\$GLOBALS\[\'TL_CONFIG\'\]\[\'' . $key . '\'\]\s*=\s*(["\']([^"\']+)["\']|([^"\']+));#',
					$localconfig,
					$match
				)
				) {
					$dbSettins->$key = isset($match[3]) ? $match[3] : $match[2];
				}
			}

			if (!isset($dbSettins->dbDriver) || !isset($dbSettins->dbUser) || !isset($dbSettins->dbDatabase)) {
				$errors[] = 'system/config/localconfig.php does not contains a suitable database setup!';
			}
			else if (stripos(strtolower($dbSettins->dbDriver), 'mysql') === false) {
				$errors[] = 'Only mysql database is supported!';
			}
			else {
				$username = $dbSettins->dbUser;
				$password = isset($dbSettins->dbPass) ? $dbSettins->dbPass : '';

				$dsn     = 'mysql:';
				$options = array();

				if (isset($dbSettins->dbHost)) {
					$dsn .= 'host=' . $dbSettins->dbHost;
				}
				else {
					$dsn .= 'host=localhost';
				}

				if (isset($dbSettins->dbPort)) {
					$dsn .= ';port=' . $dbSettins->dbPort;
				}

				$dsn .= ';dbname=' . $dbSettins->dbDatabase;

				if (isset($dbSettins->dbCharset)) {
					$options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $dbSettins->dbCharset;
				}

				$db = new PDO($dsn, $username, $password, $options);

				/* Read installed extension versions */
				$tablesStatement = $db->query('SHOW TABLES LIKE "tl_repository_installs";', PDO::FETCH_NUM);
				if (!$tablesStatement->rowCount()) {
					$errors[] = 'The table tl_repository_installs does not exists, could not detect versions of installed extensions!';
				}
				else {
					$installs = $db->query('SELECT * FROM tl_repository_installs', PDO::FETCH_OBJ);
					foreach ($installs as $install) {
						$major = intval($install->version / 10000000);
						$minor = intval($install->version / 10000) % 1000;
						$release = intval($install->version / 10) % 1000;
						$stability = $install->version % 10;

						$status->extensions[$install->extension] = (object) array(
							'name'        => $install->extension,
							'version'     => $major . '.' . $minor . '.' . $release . '.' . $install->build,
							'major'       => $major,
							'minor'       => $minor,
							'release'     => $release,
							'build'       => $install->build,
							'stability'   => $stability,
							'allowAlpha'  => (bool) $install->alpha,
							'allowBeta'   => (bool) $install->beta,
							'allowRC'     => (bool) $install->rc,
							'allowStable' => (bool) $install->stable,
							'protected'   => (bool) $install->updprot,
						);
					}
					uksort($status->extensions, 'strnatcasecmp');
				}

				/* Read users */
				$tablesStatement = $db->query('SHOW TABLES LIKE "tl_user";', PDO::FETCH_NUM);
				if (!$tablesStatement->rowCount()) {
					$errors[] = 'The table tl_user does not exists, could not find users!';
				}
				else {
					$status->users = $db
						->query('SELECT id,username,name,email,admin,disable AS disabled,locked,currentLogin FROM tl_user ORDER BY username', PDO::FETCH_OBJ)
						->fetchAll();
				}
			}
		}

		return new StatusResponseCommand($status, $errors);
	}
}
