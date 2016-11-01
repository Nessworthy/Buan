<?php
/**
 * All class-loading in a Buan application is routed through this class.
 * Class filenames must match the class name.
 *
 * @package Buan
 */
namespace Buan;

class AutoLoader
{

	/**
	 * A list of all folder paths that will be searched when loading a class.
	 *
	 * @var array
	 */
	static private $classPaths = [];

	/**
	 * Attempts to find and include a source file for the specified class.
	 * If not found, it throws a \Buan\Exception, which is caught and handled by
	 * "__autoload()".
	 *
	 * @param string $className Name of the class to load (case-sensitive)
	 * @return bool
	 * @throws \Buan\Exception
	 */
	static public function loadClass($className)
	{

		// Apply namespace alterations
		// At present this will do two checks: eg. for "Buan\Config":
		//	BuanConfig.php
		//	Buan/Config.php
		if (stripos($className, "\\")) {
			$nsClassName = str_replace("\\", "/", $className);
			$className = str_replace("\\", "", $className);
		}

		// Search all class paths
		foreach (self::$classPaths as $idx => $path) {
			$filePath = sprintf('%s/%s.php', $path, $className);
			if (is_file($filePath)) {
				include_once($filePath);
				reset(self::$classPaths);
				return true;
			} else {
				if (isset($nsClassName)) {
					$nsFilePath = sprintf('%s/%s.php', $path, $nsClassName);
					if (is_file($nsFilePath)) {
						include_once($nsFilePath);
						reset(self::$classPaths);
						return true;
					}
				}
			}
		}

		// If the class wasn't found, throw a Buan\Exception
		throw new Exception("Class '$className' was not found.");
	}

	/**
	 * Adds a folder path to the list of paths that are searched when loading
	 * classes. The filenames of each class must match the class name exactly, eg
	 * a class named "HelloWorld" must be stored in a file named "HelloWorld.php"
	 *
	 * @todo Could add another parameter here, $regex, which you could use to
	 * indicate what filename matches are stored in the given path, so the
	 * searching routine would only bother searching in $path if the given class
	 * name matches this $regex (if $regex is ommitted then any filename would be
	 * assumed).
	 *
	 * @param string $path Absolute path to a folder containing classes
	 * @return void
	 */
	static public function addClassPath($path)
	{

		// Add the specified path to the list of paths to be searched when loading a class
		self::$classPaths[] = $path;
	}
}

/**
 * @todo The exception handling doesn't seem to work if we register
 * Buan\AutoLoader::loadClass() directly in spl_autload_register(), but using
 * this global "inbetweener" function does. Might it be the fact that this
 * routine defines a new class within the scope of a class method?
 *
 * @param string $className Class to load
 * @return bool
 */
function fAutoLoader($className)
{

	// Try to load class
	try {
		AutoLoader::loadClass($className);

	} // Capture exception
	catch (Exception $e) {
		// CLASS WAS NOT FOUND. DO NOTHING.
		return false;
	}
	return true;
}

// Register the Buan\fBuanAutoLoader() function as a PHP autoloader.
spl_autoload_register(__NAMESPACE__ . '\fAutoLoader');
