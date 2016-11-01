<?php
namespace Buan;
class HttpRequest {

	private $options = array();

	private $server = array();

	/**
	* Any elements missing from the $options array will be filled with information
	* about the current request environment.
	*
	* @param array Options
	* @return Buan\HttpRequest
	*/
	public function __construct($options=array()) {
		$this->options = array_merge(array(
		), $options);
		$this->server = $_SERVER;
	}

	/**
	* Is this an XHR request?
	*
	* @return bool
	*/
	public function isXhr() {
		return isset($this->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->server['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest';
	}
}
?>