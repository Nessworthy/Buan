<?php
/**
* A method for storing and retrieving application-wide configuration variables.
* Variables are accessed through a dot-notation syntax.
*
* @package Buan
*/
namespace Buan;
class Config {

	/**
	* Stores an in-memory cache of the results of all calls to Config::get() in
	* order to speed things up. This entire cache is cleared whenever a call to
	* Config::set() is made.
	*
	* @var array
	*/
	static protected $_getCache = array();

	/**
	* Stores all variables in an associative array.
	*
	* @var array
	*/
	static protected $var = array();

	/**
	* Creates the specified namespace tree and returns a reference to it.
	*
	* @param string Namespace to create (dot-notation, eg. "app.dir.views")
	* @return array
	*/
	static public function createNamespace($namespace) {

		// Create all specified namespace nodes
		return eval("return self::\$var['".str_replace('.', "']['", $namespace)."'] = array();");
	}

	/**
	* Determines if the specified variable exists.
	*
	* @param string Check if this variable exists (dot-notation, eg. "app.dir.views")
	* @return bool
	*/
	static public function is_set($path) {
		$arrayPath = is_null($path) ? '' : "['".implode("']['", explode(".", $path))."']";
		eval('$isset = isset(self::$var'.$arrayPath.') ? TRUE : FALSE;');
		return $isset;
	}

	/**
	* Returns a config variable or a namespace tree
	*
	* @param string Variable to retreive (dot-notation)
	* @return mixed
	*/
	static public function get($path=NULL) {

		// Check memory cache first
		if(isset(self::$_getCache[$path])) return self::$_getCache[$path];

		// Find and return the specified variable/namespace tree
		$arrayPath = $path===NULL ? '' : "['".str_replace('.', "']['", $path)."']";
		return eval('return isset(self::$var'.$arrayPath.') ? self::$_getCache[$path] = self::$var'.$arrayPath.' : NULL;');
	}

	/**
	* Sets the specified config variable.
	*
	* Example:
	* <code>
	* \Buan\Config::set('app.dir', array('views'=>'A'));
	* \Buan\Config::get('app.dir.views')==='A';
	* </code>
	*
	* @param string Variable to set (dot-notation, eg. "app.dir.views")
	* @param mixed Value to store.
	* @return bool
	*/
	static public function set($varName, $varValue) {

		// Separate the namespace from the given variable name
		$lastDot = strrpos($varName, '.');
		if($lastDot===FALSE) {
			self::$var[$varName] = $varValue;
			return TRUE;
		}
		$namespace = substr($varName, 0, $lastDot);
		$varName = substr($varName, $lastDot+1);

		// Get a reference to the namespace's tree
		$arrayPath = "['".str_replace('.', "']['", $namespace)."']";
		eval('$ns =& self::$var'.$arrayPath.';');
		if($ns===NULL) {

			// Create namespace
			$ns = self::createNamespace($namespace);
		}

		// Clear cache
		self::$_getCache = array();

		// Set the variable
		$ns[$varName] = self::$_getCache["{$namespace}.{$varName}"] = $varValue;
		return TRUE;
	}
}
?>