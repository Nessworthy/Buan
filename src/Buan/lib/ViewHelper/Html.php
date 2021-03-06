<?php
/**
* Provides a number of methods specific to HTML Views.
*
* @package Buan
* @subpackage ViewHelper
*/
namespace Buan\ViewHelper;
class Html {

	/**
	* If you want all URLS generated by ::eUrl() to be suffixed with a string
	* (eg. ".html"), then set that suffix here.
	* Note that the suffix will be visible in the [app.command.requested] config
	* variable so it's your application's responsibility to strip this suffix from
	* the URL and re-store it in [app.command.requested].
	*
	* @var string
	*/
	public $urlSuffix = '';

	/**
	* The View instance to which this helper is attached.
	*
	* @var Buan\View
	*/
	private $view;

	/**
	* Constructor
	*
	* @param Buan\View The View to which this helper is being attached
	* @return Buan\ViewHelper\Html
	*/
	public function __construct($view) {

		// Store reference to $view
		$this->view = $view;
	}

	/**
	* Echos/returns the given string after converting applicable characters into
	* their HTML entity equivalents.
	*
	* @param string String to encode
	* @param bool If TRUE the string is echoed to output, or returned otherwise
	* @return string|void
	*/
	public function e($str, $echo=TRUE) {
		$strc = htmlentities($str, ENT_COMPAT, 'UTF-8');

		if(empty($strc) && !empty($str)) {
			error_log('Bad UTF8 decoding: [' . $_SERVER['REQUEST_URI'] .']');
			$strc = htmlentities(utf8_encode($str), ENT_COMPAT, 'UTF-8');
		}
		if($echo) {
			echo $strc;
		}
		else {
			return $strc;
		}
	}

	/**
	* Echos/returns the given string after converting applicable characters into
	* their HTML entitiy equivalents and adding the suffix defined in
	* $this->urlSuffix.
	*
	* @param string String to convert to a URL
	* @param bool If TRUE the string is echoed to output, or returned otherwise
	* @return string|void
	*/
	public function eUrl($str, $echo=TRUE) {
		$url = htmlentities($str, ENT_COMPAT, 'UTF-8');
		if($url[0]=='#') {
			return $url;
		}
		else if(strpos($url, "?")!==FALSE) {
			$url = str_replace("?", "{$this->urlSuffix}?", $url);
		}
		else {
			$url .= $this->urlSuffix;
		}

		if($echo) {
			echo $url;
		}
		else {
			return $url;
		}
	}

	/**
	* Sets the URL suffix that will be appended to URLs generated by $this->eUrl()
	*
	* @param string URL suffix
	* @return void
	*/
	public function setUrlSuffix($suffix) {
		$this->urlSuffix = $suffix;
	}
}
?>
