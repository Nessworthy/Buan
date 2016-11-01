<?php
/**
* Global event management. All global event listening and dispatching is handled
* via this static class.
*
* All event names are in the "lower-hyphenated-string" format
*
* @package Buan
*/
namespace Buan;
abstract class GlobalEventDispatcher {

	/**
	* Holds the instance of the EventDispatcher through which event handling will
	* be managed.
	*
	* @var Buan\EventDispatcher
	*/
	private static $dispatcher = NULL;

	/**
	* Route all static method calls through to the $dispatcher instance
	*
	* @param string Method being called
	* @param array Arguments
	*/
	public static function __callStatic($method, $args) {
		if(self::$dispatcher===NULL) {
			self::$dispatcher = new EventDispatcher();
		}
		return call_user_func_array(array(self::$dispatcher, $method), $args);
	}
}
?>