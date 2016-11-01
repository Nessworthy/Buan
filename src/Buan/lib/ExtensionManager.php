<?php
/**
* $Id$
*
* @package Buan
*/
namespace Buan;
class ExtensionManager {

	/**
	* Mapping between extension name and class file location.
	*
	* @var array
	*/
	static private $map;

	static private $singleton;

	/**
	* Create and return the singleton instance representing the specified
	* extension.
	*
	* @param string Name of the extension to get
	* @return Buan\Extension
	*/
	static public function get($extName) {
		if(isset(self::$singleton[$extName])) {
			return self::$singleton[$extName];
		}
		else {
			include_once(self::$map[$extName]);
			return self::$singleton[$extName] = new $extName();
		}
	}

	/**
	* Register the specified extension(s) so they can be used within the
	* application.
	*
	* This process does very little (some config setting) and is kept
	* intentionally lightweight so that the developer can run the more complicated
	* stuff if and when they actually need to.
	*
	* At this point, the [ext.ExtensionName.*] namespace is created in the global
	* config object (where "ExtensionName" is the class name of the extension, in
	* UpperCamelCaps notation).
	*
	* @param string|array
	* @return void
	*/
	static public function register() {

		// Flatten all arguments into a single array for processing
		$classes = array();
		$args = func_get_args();
		foreach($args as $a) {
			if(is_array($a)) {
				$classes = array_merge($a);
			}
			else {
				$classes[] = $a;
			}
		}

		// Register each given class
		foreach($classes as $class) {

			// Store a mapping between extension name and class location
			$extName = substr(basename($class), 0, -4);
			self::$map[$extName] = $class;

			// Create the [ext.*] namespace and some default variables
			Config::set("ext.{$extName}", array(
				'dir'=>array(
					'controllers'=>NULL
				),
				'docRoot'=>dirname($class),
				'urlRoot'=>"/".dirname($class)
			));
		}
	}
}
?>