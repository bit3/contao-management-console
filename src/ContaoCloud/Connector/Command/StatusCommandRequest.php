<?php

namespace ContaoCloud\Connector\Command;

use PDO;
use ContaoCloud\Connector\Settings;

class StatusCommandRequest extends AbstractCommandRequest
{
	public static function create($config)
	{
		return new StatusCommandRequest($config);
	}

	protected function __construct($config)
	{
	}

	public function execute(Settings $settings)
	{
		$status = (object) array(
			'version'            => -1,
			'build'              => -1,
			'lts'                => false,
			'modules'            => array(),
			'disabledModules'    => array(),
			'extensions'         => array(),
			'users'              => array()
		);

		if ($this->prepareFilesystemAccess($settings)) {
			/* ***** Read constants like VERSION, BUILD and LONG_TERM_SUPPORT ******************************************* */
			/* Contao 3+ */
			$constantsFile = $this->contaoInstallation->getFile('system/config/constants.php');
			/* Contao 2+ */
			if (!$constantsFile->exists()) {
				$constantsFile = $this->contaoInstallation->getFile('system/constants.php');
			}

			if (!$constantsFile->exists()) {
				$this->errors[] = sprintf(
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
			$modulesDir = $this->contaoInstallation->getFile('system/modules');
			if (!$modulesDir->isDirectory()) {
				$this->errors[] = sprintf(
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
			if ($this->prepareLocalconfig($settings)) {
				/* Read incompatible versions */
				$inactiveModules = $this->searchConfigEntry('inactiveModules');
				if ($inactiveModules) {
					$status->disabledModules = unserialize($inactiveModules);
					natcasesort($status->disabledModules);
				}

				if ($this->prepareDatabaseConnection($settings)) {
					/* Read installed extension versions */
					$tablesStatement = $this->dbConnection->query('SHOW TABLES LIKE "tl_repository_installs";', PDO::FETCH_NUM);
					if (!$tablesStatement->rowCount()) {
						$this->errors[] = 'The table tl_repository_installs does not exists, could not detect versions of installed extensions!';
					}
					else {
						$installs = $this->dbConnection->query('SELECT * FROM tl_repository_installs', PDO::FETCH_OBJ);
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
					$tablesStatement = $this->dbConnection->query('SHOW TABLES LIKE "tl_user";', PDO::FETCH_NUM);
					if (!$tablesStatement->rowCount()) {
						$this->errors[] = 'The table tl_user does not exists, could not find users!';
					}
					else {
						$status->users = $this->dbConnection
							->query('SELECT id,username,name,email,admin,disable AS disabled,locked,currentLogin FROM tl_user ORDER BY username', PDO::FETCH_OBJ)
							->fetchAll();
					}
				}
			}
		}

		return new StatusCommandResponse($status, $this->errors);
	}
}
