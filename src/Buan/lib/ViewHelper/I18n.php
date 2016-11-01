<?php
/**
* Enables specified strings within a View's buffer to be translated to any other
* language.
*
* Example usage (from within your custom View class' constructor):
* <code>
*	$this->i18n = new Buan\ViewHelper\I18n($this);
* </code>
*
* @package Buan
* @subpackage ViewHelper
*/
namespace Buan\ViewHelper;
class I18n {

	/**
	* The default language code.
	*
	* @var string
	*/
	const DEFAULT_LANG = 'en';

	/**
	* List of folders that contain dictionary files.
	*
	* @var array
	*/
	private $dictionaryFolders = array();

	/**
	* The language to use for all translations in the View attached to this
	* helper.
	*
	* @var string
	*/
	private $language;

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
	* @return Buan\ViewHelper\I18n
	*/
	public function __construct($view) {

		// Set some defaults
		$this->language = self::DEFAULT_LANG;

		// Add "onrender" event to View
		$this->view = $view;
		$this->view->addEventListener('onrender', array($this, 'translateView'));
	}

	/**
	* Add translation dictionary folder. This folder must contain a file for
	* each language you want to , ie. "en.php", "es.php", etc.
	*
	* Each of these files must return an array of source=>translation entries, for
	* example:
	* <code>
	*	// es.php
	*	return array (
	*		'hello'=>'ola',
	*		'goodbye, %s'=>'adios, %s'
	*	);
	* </code>
	*
	* @var string Absolute folder path
	* @return void
	*/
	public function addDictionaryFolder($src) {
		if(is_array($src)) {
			$this->dictionaryFolders = array_merge($this->dictionaryFolders, $src);
		}
		else {
			$this->dictionaryFolders[] = $src;
		}
	}

	/**
	* Return the currently set language code.
	*
	* @return string
	*/
	public function getLanguage() {
		return $this->language;
	}

	/**
	* Set the language to which $this->view will be translated.
	*
	* $code can be any string you want, but it may be good advice to follow the
	* ISO standards for language codes.
	*
	* @param string Language code
	* @return void
	*/
	public function setLanguage($code) {
		$this->language = $code;
	}

	/**
	* Translates all [t:...] strings in $this->view's buffer to the language
	* specified by $this->language. The dictionary for this language will be
	* searched for in the list of $this->dictionaryFolders.
	*
	* Examples of strings and their conversions:
	* <code>
	* [t:Hello] ... Hello
	* [t:Dave:Hello, %s] ... Hello, Dave
	* [t:Foo:Bar:I'm %s, and you're %s] ... I'm Foo, and you're Bar
	* [t:See\: www.thundermonkey.net] ... See: www.thundermonkey.net
	* </code>
	*
	* @return void
	*/
	public function translateView() {

		// Determine if there are any strings to be translated in $source
		$source =& $this->view->buffer;
		if(preg_match_all("/\[t:([^\]]+)\]/ims", $source, $matches)>0) {

			// Load global dictionary
			$dictionary = array();
			foreach($this->dictionaryFolders as $dict) {
				$file = "{$dict}/{$this->language}.php";
				if(!is_file($file)) {
					if(!is_file("{$dict}/".self::DEFAULT_LANG.".php")) {
						continue;
					}
					$file = "{$dict}/".self::DEFAULT_LANG.".php";
				}
				$dictionary = array_merge($dictionary, include($file));
			}

			// Replace all [t:*] placeholders in the $this->view's buffer.
			// If a string cannot be translated, then it is unaltered.
			$delim = "[[[SPLIT]]]";
			$tags = $matches[0];
			$translations = array();
			$missing = array();
			foreach($matches[1] as $match) {
				$params = array();
				if(strpos($match, ":")!==FALSE) {
					$match = preg_replace("/([^\\\\]):/", "$1{$delim}", $match);
					$match = str_replace('\:', ":", $match);
					$params = explode($delim, $match);
					$match = array_pop($params);
				}
				if(isset($dictionary[$match])) {
					$translations[] = count($params)>0 ? vsprintf($dictionary[$match], $params) : $dictionary[$match];
					$cache[$match] = $dictionary[$match];
				}
				else {
					// Replace with untranslated string
					$translations[] = count($params)>0 ? vsprintf($match, $params) : $match;
					$missing[] = $match;
					$cache[$match] = $match;
				}
			}
			$source = str_replace($tags, $translations, $source);
		}
	}
}
?>