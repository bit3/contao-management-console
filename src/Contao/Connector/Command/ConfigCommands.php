<?php

namespace Contao\Connector\Command;

use PDO;
use Contao\Connector\Settings;

class ConfigCommands extends AbstractCommands
{
	public function all()
	{
		$config = array();

		if ($this->prepareLocalconfig()) {
			preg_match_all(
				'#\$GLOBALS\[\'TL_CONFIG\'\]\[\'([^\']*)\'\]\s*=\s*(["\']([^\']*)["\']|([^"\']+));#',
				$this->localconfig,
				$matches,
				PREG_SET_ORDER
			);

			foreach ($matches as $match) {
				$key = $match[1];

				$value = isset($match[4]) ? $match[4] : $match[3];

				if ($value == 'true') {
					$value = true;
				}
				else if ($value == 'false') {
					$value = false;
				}
				else {
					$value = $this->saveUnserialize($value);
				}

				$config[$key] = $value;
			}
		}

		return (object) array(
			'config' => (object) $config,
			'errors' => $this->errors
		);
	}

	public function get($key)
	{
		$value = null;

		if ($this->prepareLocalconfig()) {
			$value = $this->searchConfigEntry($key);
		}

		return (object) array(
			'value'  => $value,
			'errors' => $this->errors
		);
	}

	public function set($key, $value)
	{
		$success = false;

		if ($key == 'inactiveModules') {
			$this->errors[] = 'Use enable()/disable() to enable/disable modules!';
		}
		else if ($this->prepareLocalconfig()) {
			$success = $this->replaceConfigEntry(
				$key,
				$value,
				true
			);
		}

		return (object) array(
			'success' => $success,
			'errors'  => $this->errors
		);
	}

	public function remove($key)
	{
		$success = false;

		if ($key == 'inactiveModules') {
			$this->errors[] = 'Use enable()/disable() to enable/disable modules!';
		}
		else if ($this->prepareLocalconfig()) {
			$success = $this->removeConfigEntry(
				$key,
				true
			);
		}

		return (object) array(
			'success' => $success,
			'errors'  => $this->errors
		);
	}

	protected function flatten($array, &$target = array())
	{
		foreach ($array as $item) {
			if (is_array($item)) {
				$this->flatten($item, $target);
			}
			else {
				$target[] = $item;
			}
		}

		return $target;
	}

	public function enable($modules)
	{
		$inactiveModules = null;

		$modules = array_filter(
			array_map(
				'trim',
				$this->flatten(func_get_args())
			)
		);

		if ($this->prepareLocalconfig()) {
			// add .skip files for contao 3+
			if (version_compare($this->getContaoVersion(), '3', '>=')) {
				foreach ($modules as $module) {
					$skipFile = $this->contaoInstallation->getFile('system/modules/' . $module . '/.skip');
					if ($skipFile->exists()) {
						$skipFile->delete();
					}
				}
			}

			$inactiveModules = array_filter(
				(array) $this->searchConfigEntry('inactiveModules')
			);

			$inactiveModules = array_diff(
				$inactiveModules,
				$modules
			);
			$this->replaceConfigEntry('inactiveModules', $inactiveModules, true);

			$inactiveModules = array_filter(
				(array) $this->searchConfigEntry('inactiveModules')
			);
		}

		return (object) array(
			'inactiveModules' => $inactiveModules,
			'errors'          => $this->errors
		);
	}

	public function disable($modules)
	{
		$inactiveModules = null;

		$modules = array_filter(
			array_map(
				'trim',
				$this->flatten(func_get_args())
			)
		);

		if ($this->prepareLocalconfig()) {
			// add .skip files for contao 3+
			if (version_compare($this->getContaoVersion(), '3', '>=')) {
				foreach ($modules as $module) {
					$skipFile = $this->contaoInstallation->getFile('system/modules/' . $module . '/.skip');
					if (!$skipFile->exists() && $skipFile
						->getParent()
						->isDirectory()
					) {
						$skipFile->createFile();
					}
				}
			}

			$inactiveModules = array_filter(
				(array) $this->searchConfigEntry('inactiveModules')
			);

			$inactiveModules = array_merge(
				$inactiveModules,
				$modules
			);
			$inactiveModules = array_unique($inactiveModules);
			$this->replaceConfigEntry('inactiveModules', $inactiveModules, true);

			$inactiveModules = array_filter(
				(array) $this->searchConfigEntry('inactiveModules')
			);
		}

		return (object) array(
			'inactiveModules' => $inactiveModules,
			'errors'          => $this->errors
		);
	}
}
