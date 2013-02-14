<?php

namespace ContaoManagementApi\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;
use Composer\Autoload\ClassLoader;
use Filicious\File;
use Filicious\Filesystem;
use Filicious\Local\LocalAdapter;
use Traversable;

class BundlerPackCommand extends Command
{
	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * @var string
	 */
	protected $basepath;

	/**
	 * @var Filesystem
	 */
	protected $fs;

	/**
	 * @var ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var array
	 */
	protected $addedFiles;

	/**
	 * @var array
	 */
	protected $interfaces;

	/**
	 * @var array
	 */
	protected $classes;

	/**
	 * @var array
	 */
	protected $others;

	protected function configure()
	{
		parent::configure();

		$this
			->setName('bundler:pack')
			->setDescription('Create bundled executable')
			->addOption(
			'output',
			'o',
			InputOption::VALUE_REQUIRED,
			'Write to file instead of stdout',
			'php://stdout'
		)
			->addOption(
			'private-key-file',
			'K',
			InputOption::VALUE_REQUIRED,
			'Path to the private key file'
		)
			->addOption(
			'private-key',
			null,
			InputOption::VALUE_REQUIRED,
			'The private key'
		)
			->addOption(
			'public-key-file',
			'P',
			InputOption::VALUE_REQUIRED,
			'Path to the public key file'
		)
			->addOption(
			'public-key',
			null,
			InputOption::VALUE_REQUIRED,
			'The public key'
		)
			->addOption(
			'contao-path',
			'p',
			InputOption::VALUE_REQUIRED,
			'Relative path from the management api to the contao installation base path',
			'../'
		)
			->addOption(
			'log',
			'l',
			InputOption::VALUE_REQUIRED,
			'Relative path from the management api to the log file (e.g. connect.log)'
		)
			->addOption(
			'log-name',
			'N',
			InputOption::VALUE_REQUIRED,
			'Logger name',
			'contao-management-api'
		)
			->addOption(
			'log-level',
			'L',
			InputOption::VALUE_REQUIRED,
			'Set the log level [DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY]',
			'ERROR'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$out    = $input->getOption('output');

		if ($out == 'php://stdout') {
			$this->output = $output->getErrorOutput();
		}
		else {
			$this->output = $output;
		}

		$this->basepath = dirname(dirname(dirname(__DIR__)));

		$this->output->writeln(' <info>*</info> Bundle files from ' . $this->basepath);

		$this->fs = new Filesystem(
			new LocalAdapter(
				$this->basepath
			)
		);

		$this->addedFiles = array();
		$this->interfaces = array();
		$this->classes    = array();
		$this->others     = array();

		$autoloaders = spl_autoload_functions();
		$this->classLoader = null;
		foreach ($autoloaders as $autoloader) {
			if (is_array($autoloader) && $autoloader[0] instanceof ClassLoader) {
				$this->classLoader = $autoloader[0];
				break;
			}
		}
		if (!$this->classLoader) {
			throw new \Exception('Could not find class loader.');
		}

		// add src files and autodiscover dependencies
		$this->output->writeln(' <info>*</info> Adding management api files');

		$srcDir   = $this->fs->getFile('src');
		$iterator = $srcDir->getIterator(
			File::LIST_RECURSIVE,
			function ($pathname) {
				return strpos($pathname, '/Console/') === false;
			}
		);
		$this->addFiles($iterator);

		// add hard coded dependencies, that cannot be autodiscovered
		$this->addFile($this->fs->getFile('vendor/phpseclib/phpseclib/phpseclib/Crypt/Random.php'));
		$this->addFile($this->fs->getFile('vendor/filicious/filicious/src/Filicious/Stream/StreamWrapper.php'));

		// sort files
		$this->interfaces = $this->sortFiles($this->interfaces);
		$this->classes    = $this->sortFiles($this->classes);

		// add error handler
		$errorHandlerFile = $this->fs->getFile('scripts/error_handler.php');
		$this->addDependencies($errorHandlerFile);
		$this->others[] = $errorHandlerFile;

		// add execution script
		$runScriptFile = $this->fs->getFile('scripts/connect.php');
		$this->addDependencies($runScriptFile);
		$this->others[] = $runScriptFile;

		$this->output->writeln(
			sprintf(
				' <info>*</info> Bundled %d interfaces, %d classes and %d other resource',
				count($this->interfaces),
				count($this->classes),
				count($this->others)
			)
		);

		// create buffer
		$buffer = fopen($out, 'wb');
		fwrite(
			$buffer,
			<<<EOF
<?php

/**
 * This file contains a bunch of files, bundled into one single file.
 *
 * See http://contao-cloud.com for more details about this script
 * and all scripts used to generate this bundle.
 */


EOF
		);

		$privateKeyFile = $input->getOption('private-key-file');
		$privateKey = $input->getOption('private-key');
		$publicKeyFile = $input->getOption('public-key-file');
		$publicKey = $input->getOption('public-key');
		$contaoPath = $input->getOption('contao-path');
		$log = $input->getOption('log');
		$logName = $input->getOption('log-name');
		$logLevel = $input->getOption('log-level');

		// add files to buffer
		foreach ($this->interfaces as $interface) {
			$this->writeFile($interface->file, $buffer);
		}
		foreach ($this->classes as $class) {
			$this->writeFile($class->file, $buffer);
		}

		// add global namespace
		fwrite($buffer, "namespace {\n");

		// add constants to buffer
		if ($privateKeyFile && file_exists($privateKeyFile)) {
			$privateKey = file_get_contents($privateKeyFile);
		}
		if ($privateKey) {
			$this->output->writeln(' <info>*</info> Add private key');

			$privateKey = var_export($privateKey, true);

			fwrite($buffer, <<<EOF
define('CONTAO_MANAGEMENT_API_RSA_LOCAL_PRIVATE_KEY', $privateKey);

EOF
			);
		}

		if ($publicKeyFile && file_exists($publicKeyFile)) {
			$publicKey = file_get_contents($publicKeyFile);
		}
		if ($publicKey) {
			$this->output->writeln(' <info>*</info> Add public key');

			$publicKey = var_export($publicKey, true);

			fwrite($buffer, <<<EOF
define('CONTAO_MANAGEMENT_API_RSA_REMOTE_PUBLIC_KEY', $publicKey);

EOF
			);
		}

		$contaoPath = var_export('/' . $contaoPath, true);
		fwrite($buffer, <<<EOF
define('CONTAO_MANAGEMENT_API_CONTAO_PATH', realpath(dirname(__FILE__) . $contaoPath));

EOF
		);

		if ($log) {
			$this->output->writeln(' <info>*</info> Activate logging to ' . $log);

			$log = var_export('/' . $log, true);

			$logLevel = strtoupper($logLevel);
			$class = new \ReflectionClass('\Monolog\Logger');
			if ($class->hasConstant($logLevel)) {
				$logLevel = $class->getConstant($logLevel);
			}
			else {
				$logLevel = (int) $logLevel;
			}

			fwrite($buffer, <<<EOF
define('CONTAO_MANAGEMENT_API_LOG', dirname(__FILE__) . $log);
define('CONTAO_MANAGEMENT_API_LOG_LEVEL', $logLevel);

EOF
			);

			if ($logName != 'contao-management-api') {
				$logName = var_export($logName, true);
				fwrite($buffer, <<<EOF
define('CONTAO_MANAGEMENT_API_LOG_NAME', $logName);

EOF
				);
			}
		}

		// add non-namespace files to buffer
		foreach ($this->others as $file) {
			$this->writeFile($file, $buffer, false);
		}

		// close global namespace
		fwrite($buffer, "}\n");

		// output the bundled script
		fflush($buffer);
		fclose($buffer);
	}

	public function normaliseClassName($className)
	{
		$parts = explode('\\', $className);
		$parts = array_filter($parts);
		return implode('\\', $parts);
	}

	protected function addDirectories($path)
	{
		$dir      = $this->fs->getFile($path);
		$iterator = $dir->getIterator(
			File::LIST_RECURSIVE
		);
		$this->addFiles($iterator);
	}

	/**
	 * @param array    $files
	 * @param resource $buffer
	 */
	protected function addFiles(Traversable $iterator)
	{
		foreach ($iterator as $file) {
			$this->addFile(
				$file
			);
		}
	}

	/**
	 * @param array    $files
	 * @param resource $buffer
	 */
	protected function addFile(File $file, $dependency = false)
	{
		if (in_array($file->getPathname(), $this->addedFiles)) {
			return;
		}
		$this->addedFiles[] = $file->getPathname();

		$this->output->writeln(
			sprintf(
				' <info>*</info> Add %s %s',
				$dependency ? 'dependency' : 'file',
				$file->getPathname()
			)
		);

		$content = $file->getContents();

		if (preg_match(
			'#((?:abstract\s+)?class|interface)\s+([^\s]+)\s+(?:extends(.*))?(?:implements(.*))?\{#sU',
			$content,
			$match
		)
		) {
			$name    = trim($match[2]);
			$extends = array();

			if (isset($match[3])) {
				$extends = explode(',', $match[3]);
				$extends = array_map('trim', $extends);
				$extends = array_filter($extends);
			}

			if (isset($match[4])) {
				$implements = explode(',', $match[4]);
				$implements = array_map('trim', $implements);
				$implements = array_filter($implements);
				// handle implements same as extends
				$extends = array_merge($extends, $implements);
			}

			$isInterface = $match[1] == 'interface';
			$isAbstract  = $match[1] == 'abstract class';

			if (preg_match('#namespace ([^;]+);#', $content, $match)) {
				$namespace = static::normaliseClassName($match[1]);
				$name      = $namespace . '\\' . $name;
			}
			else {
				$namespace = '';
			}

			$uses = $this->addDependencies($file, $namespace);

			foreach ($extends as $k => $v) {
				if (isset($uses[$v])) {
					$extends[$k] = static::normaliseClassName($uses[$v]);
				}
				else if ($namespace && $v[0] !== '\\') {
					$extends[$k] = static::normaliseClassName($namespace . '\\' . $v);
				}
				else {
					$extends[$k] = static::normaliseClassName($v);
				}
			}

			$item = (object) array(
				'namespace'   => $namespace,
				'name'        => $name,
				'file'        => $file,
				'extends'     => $extends,
				'isInterface' => $isInterface,
				'isAbstract'  => $isAbstract
			);

			if ($isInterface) {
				$this->interfaces[$item->name] = $item;
			}
			else {
				$this->classes[$item->name] = $item;
			}

			foreach ($extends as $extendsClass) {
				$extendsFilename = $this->classLoader->findFile($extendsClass);
				if ($extendsFilename) {
					$extendsPathname = substr($extendsFilename, strlen($this->basepath));
					$extendsFile = $this->fs->getFile($extendsPathname);
					$this->addFile($extendsFile, true);
				}
				else if (!class_exists($extendsClass, false)) {
					$this->output->writeln(' <error>*</error> Missing dependency ' . $extendsClass);
				}
			}
		}
		else {
			$this->addDependencies($file);

			$this->others[] = $file;
		}
	}

	protected function addDependencies(File $file, $namespace = '\\')
	{
		$content = $file->getContents();

		$uses = array();
		if (
			preg_match_all(
				'#\\n\\s*use\s+([\w\\\\]+)(?:\s+as\s+([^;]))?#i',
				$content,
				$matches,
				PREG_SET_ORDER
			)
		) {
			foreach ($matches as $use) {
				$useClass = static::normaliseClassName($use[1]);
				$useName  = static::normaliseClassName(
					isset($use[2])
						? trim($use[2])
						: preg_replace('#^.*\\\\([^\\\\]*?)$#', '$1', $use[1])
				);

				$uses[$useName] = $useClass;
			}
		}

		foreach ($uses as $useClass) {
			$useFilename = $this->classLoader->findFile($useClass);
			if ($useFilename) {
				$usePathname = substr($useFilename, strlen($this->basepath));
				$useFile = $this->fs->getFile($usePathname);
				$this->addFile($useFile, true);
			}
			else if (!class_exists($useClass, false)) {
				$this->output->writeln(' <error>*</error> Missing dependency ' . $useClass);
			}
		}

		// remove comments before analyse code
		$content = preg_replace(
			'#//.*#',
			'',
			$content
		);
		$content = preg_replace(
			'#/\*.*\*/#sU',
			'',
			$content
		);

		// search instantiations and static calls
		if (
			preg_match_all(
				'#new ([\\\\\w]+)\(|([\\\\\w]+)::#',
				$content,
				$matches,
				PREG_SET_ORDER
			)
		) {
			foreach ($matches as $instantiation) {
				$instantiationClass = isset($instantiation[2]) ? $instantiation[2] : $instantiation[1];
				if (
					$instantiationClass == 'self' ||
					$instantiationClass == 'static' ||
					$instantiationClass == 'parent'
				) {
					continue;
				}
				if (isset($uses[$instantiationClass])) {
					$instantiationClass = static::normaliseClassName($uses[$instantiationClass]);
				}
				else if ($namespace && $instantiationClass[0] !== '\\') {
					$instantiationClass = static::normaliseClassName($namespace . '\\' . $instantiationClass);
				}
				else {
					$instantiationClass = static::normaliseClassName($instantiationClass);
				}
				$instantiationFilename = $this->classLoader->findFile($instantiationClass);
				if ($instantiationFilename) {
					$instantiationPathname = substr($instantiationFilename, strlen($this->basepath));
					$instantiationFile = $this->fs->getFile($instantiationPathname);
					$this->addFile($instantiationFile, true);
				}
				else if (!class_exists($instantiationClass, false)) {
					$this->output->writeln(' <error>*</error> Missing dependency ' . $instantiationClass);
				}
			}
		}

		return $uses;
	}

	protected function sortFiles(array $classes)
	{
		$result = array();

		while (count($classes)) {
			$class = array_shift($classes);

			if (count($class->extends)) {
				foreach ($class->extends as $extend) {
					// skip dependency, because this is not a vendor class
					if (!isset($classes[$extend])) {
						continue;
					}

					// skip class and add later, because this dependency is not added yet
					if (!isset($result[$extend])) {
						$classes[$class->name] = $class;
						continue 2;
					}
				}
			}

			$result[$class->name] = $class;
		}

		return $result;
	}

	/**
	 * @param array    $files
	 * @param resource $buffer
	 */
	protected function writeFile(File $file, $buffer, $fallbackToGlobalNamespace = true)
	{
		if ($file->isFile()) {
			$content = $file->getContents();

			// remove php tags
			$content = str_replace(
				array('<?php', '?>'),
				'',
				$content
			);

			$content = trim($content);

			// encapsulate namespace into {} block
			if (preg_match('#(namespace [\w\d\\\\]+);#', $content)) {
				$content = preg_replace(
					'#(namespace [\w\d\\\\]+);#',
					'$1 {',
					$content
				);
				$content .= "\n}\n";
			}
			else if ($fallbackToGlobalNamespace) {
				$content = "namespace {\n" . $content . "\n}\n";
			}

			fwrite($buffer, "\n");
			fwrite($buffer, '// source ' . $file->getPathname() . "\n\n");
			fwrite($buffer, $content . "\n");
		}
	}
}
