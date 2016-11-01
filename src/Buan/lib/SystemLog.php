<?php
/**
* This is the global logging object in which all events, errors, etc are recorded. (static class).
*
* TODO:
* Need a method of using a custom logging class, perhaps something like:
*	SystemLog::addCustomLogger(new MyCustomLogger())
*
* @package Buan
*/
namespace Buan;
class SystemLog {

	/*
	# @properties Constants for each type of log message
	#
	# TODO: Need to get down a list of where to use each type of log message for some consitency. Also perhaps rename error
	#	related messages to "E_*" and feedback/information messages to "I_*". Also introduce user-defined message
	#	types prefixed with "U", eg. "UE_NOTICE", "UI_ALERT"
	#
	# TYPE_INFO	= General, non-critical information feedback
	# TYPE_NOTICE	=
	# TYPE_WARNING	=
	# TYPE_FATAL	= 
	*/
	const CORE = 1;			// Internal log message related to the core, eg. general information messages generated within the core
	const NOTICE = 2;		// Non-fatal and has no adverse side-effects, eg. 
	const WARNING = 4;		// Non-fatal, but may affect the outcome, eg. View is missing a template source file.
	const FATAL = 8;		// Fatal error, application will halt immediately
	const INFO = 16;		// General information feedback

	/*
	# @property array $typeString
	# Holds a more friendly string description of each log message type.
	*/
	static public $typeString = array(
		self::CORE=>'core',
		self::NOTICE=>'notice',
		self::WARNING=>'warning',
		self::FATAL=>'fatal',
		self::INFO=>'info'
	);

	/*
	# @property array $log
	# Stores all log data. Each entry is an instance of SystemLogEntry.
	*/
	static $log = array();

	/**
	* Adds an entry to the message log.
	* If you pass the $message as an array, then the first element in that array
	* is treated as the textual message and all subsequent elements are inserted
	* into placeholders within that message using sprintf().
	*
	* @param string Log message
	* @param int Message type
	* @param int Unique code for this message
	* @param bool If TRUE then the message will be treated as containing HTML
	* @return void
	*/
	static function add($message, $type=SystemLog::CORE, $code=0, $isHtml=FALSE) {

		// Add to the log
		self::$log[] = new SystemLogEntry($message, $type, $code, $isHtml);
	}

	/*
	# @method array getAll()
	#
	# Returns all entries in the log
	*/
	static function getAll() {

		// Result
		return self::$log;
	}

	/*
	# @method array getLast()
	#
	# Returns the last entry that was entered into the log.
	*/
	static public function getLast() {

		// Result
		return count(self::$log)>0 ? self::$log[count(self::$log)-1] : new SystemLogEntry('', 0, 0);
	}
}
?>
