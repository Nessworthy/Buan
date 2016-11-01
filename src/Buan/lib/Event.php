<?php
/**
* An event wrapper.
*
* @package Buan
*/
namespace Buan;
class Event {

	/**
	* Name of this event.
	*
	* @var string
	*/
	public $name = NULL;

	/**
	* Storage for passing data within the event so it persists from one listener
	* to the next
	*
	* @var StdClass
	*/
	public $data;

	/**
	* Constructor.
	*
	* @param string Event name
	* @return Buan\Event
	*/
	public function __construct($name) {
		$this->name = $name;
		$this->data = new \StdClass();
	}
}
?>