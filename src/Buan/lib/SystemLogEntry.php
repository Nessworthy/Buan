<?php
/**
* @package Buan
*/
namespace Buan;
class SystemLogEntry {

	/*
	# @property string $message
	# Log message.
	*/
	private $message;

	/*
	# @property array $messageVars
	# List of values that will be inserted into the $this->message at render-time
	# using sprintf().
	*/
	private $messageVars;

	/*
	# @property int $type
	# Type of message.
	*/
	private $type;

	/*
	# @property string $code
	# A unique code that identifies the message within the system.
	*/
	private $code;

	/*
	# @property array $callStack
	# Stores the results of debug_backtrace()
	*/
	private $callStack;

	/*
	# @method void __construct( string|array $message, int $type, string $code )
	# $message	= Log message
	# $type		= Message type
	# $code		= A unique identifier for the message
	#
	# Creates a new SystemLogEntry object.
	*/
	public function __construct($message, $type, $code) {

		// Store properties
		if(is_array($message)) {
			$this->message = array_shift($message);
			$this->messageVars = $message;
		}
		else {
			$this->message = $message;
			$this->messageVars = array();
		}
		$this->type = $type;
		$this->code = $code;
		$this->callStack = debug_backtrace();
	}

	/*
	# @method string getMessage( [bool $raw] )
	# $raw	= If TRUE then the message is returned with no variable-substitution.
	#
	# Returns this entry's message.
	*/
	public function getMessage($raw=FALSE) {

		// Result
		return $raw || empty($this->messageVars) ? $this->message : vsprintf($this->message, $this->messageVars);
	}

	/*
	# @method int getType()
	#
	# Returns an integer representing this entry's message type (see SystemLog for type constants).
	*/
	public function getType() {

		// Result
		return $this->type;
	}

	/*
	# @method string getTypeString()
	#
	# Returns a string-version of the message type (see SystemLog for type strings).
	*/
	public function getTypeString() {

		// Result
		return SystemLog::$typeString[$this->type];
	}

	/*
	# @method string getCode()
	#
	# Returns the message's unique code.
	*/
	public function getCode() {

		// result
		return $this->code;
	}

	/*
	# @method array getCallStack()
	#
	# Returns the call stack.
	*/
	public function getCallStack() {

		// Result
		return $this->callStack;
	}
}
?>