<?php
/**
* $Id$
*/

// Dependencies
use Buan\Config;
use Buan\Controller;
use Buan\Core;
use Buan\Extension;
use Buan\SystemLog;
use Buan\UrlCommand;
use Buan\View;
use Buan\ViewHelper\Html as HtmlView;

// Class
class CoreController extends Controller {

	/*
	# @method View appLogin( array $params )
	# $params	= Action parameters
	#
	# The application-login interface.
	*/
	public function appLogin($params) {

		// Determine where to redirect to once logged in
		$refererUrl = isset($_POST['refererUrl']) ? $_POST['refererUrl'] : (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "http://".UrlCommand::createAbsoluteUrl('core'));

		// Test
		if(isset($_POST['password'])) {
			if($_POST['password']==Config::get('app.password')) {

				// Grant admin access
				Core::setAdminAccess(TRUE);
	
				// Redirect to referring URL
				header("Location: $refererUrl");
				exit();
			}
			else {
				SystemLog::add('Login incorrect.', SystemLog::WARNING);
			}
		}

		// Prepare view
		$view = new View();
		$view->setSource(Config::get('core.dir.views').'/core/app-login.tpl.php');
		$view->rUrl = $refererUrl;
		return $this->wrapper($view);
	}

	/**
	* Performs a series of diagnostic tests on the current application setup to
	* ensure all necessary settings and access rights are correct.
	*
	* @param array
	* @return Buan\View
	*/
	public function diagnostic($params) {

		// Check login
		if(!Core::hasAdminAccess()) {
			return Core::getLoginView($_SERVER['REDIRECT_URL']);
		}

		// View
		$view = new View();
		$view->setSource(Config::get('core.dir.views').'/core/diagnostic.tpl.php');

		// Check permissions on folders
		$permissionDirs = array(
			'temp'=>array(
				'name'=>'Temporary storage folder',
				'path'=>Config::get('app.dir.temp'),
				'requiresWrite'=>TRUE,
				'passed'=>FALSE,
				'testMessage'=>''
			),
			'controllers'=>array(
				'name'=>'Application Controllers folder',
				'path'=>Config::get('app.dir.controllers'),
				'requiresWrite'=>FALSE,
				'passed'=>FALSE,
				'testMessage'=>''
			),
			'models'=>array(
				'name'=>'Application Model folder',
				'path'=>Config::get('app.dir.models'),
				'requiresWrite'=>FALSE,
				'passed'=>FALSE,
				'testMessage'=>''
			),
			'modelManagers'=>array(
				'name'=>'Application ModelManagers folder',
				'path'=>Config::get('app.dir.modelManagers'),
				'requiresWrite'=>FALSE,
				'passed'=>FALSE,
				'testMessage'=>''
			)
		);
		foreach($permissionDirs as $k=>$dir) {
			if($dir['requiresWrite']) {
				$permissionDirs[$k]['passed'] = is_dir($dir['path']) && is_writable($dir['path']);
			}
			else {
				$permissionDirs[$k]['passed'] = is_dir($dir['path']) && is_readable($dir['path']);
			}
		}
		$view->permissionDirs = $permissionDirs;

		// PHP config settings
		$phpConfigs = array();
		$phpConfigs['version'] = array(
			'name'=>'Version 5.2 or higher',
			'passed'=>PHP_VERSION>=5.2
		);
		$phpConfigs['buffering'] = array(
			'name'=>'Output buffering enabled',
			'passed'=>strtolower(ini_get('output_buffering'))=='on' || (int)ini_get('output_buffering')>0
		);
		$phpConfigs['bufferFlush'] = array(
			'name'=>'Implicit flushing disabled',
			'passed'=>strtolower(ini_get('implicit_flush'))=='off' || ini_get('implicit_flush')=='',
			'actual'=>ini_get('implicit_flush')
		);
		$phpConfigs['magicQuotes'] = array(
			'name'=>'Magic quotes disabled',
			'passed'=>strtolower(ini_get('magic_quotes_gpc'))=='off' || ini_get('magic_quotes_gpc')=='',
			'actual'=>ini_get('magic_quotes_gpc')
		);
		$phpConfigs['shortTags'] = array(
			'name'=>'Short PHP open tag (<? rather than <?p'.'hp) disabled',
			'passed'=>strtolower(ini_get('short_open_tag'))=='off' || (int)ini_get('short_open_tag')==0
		);
		$view->phpConfigs = $phpConfigs;

		// Check if the core public resources directory is accessible via a URL
		$apacheTests = array();
		$relativeUrl = Config::get('core.url.resources');
		$apacheTests['buan-views'] = array(
			'name'=>'Core views directory is accessible via URL http://'.Config::get('app.domain').$relativeUrl,
			'passed'=>TRUE,
			'resolution'=>''
		);
		$fp = fsockopen(Config::get('app.domain'), 80, $errn, $errstr, 30);
		if(!$fp) {
			$apacheTests['buan-views']['passed'] = FALSE;
			$apacheTests['buan-views']['resolution'] = "Connection to the URL completely failed. Check PHP support <b>fsockopen</b>.";
		}
		else {
			$out = "GET $relativeUrl HTTP/1.1\r\n";
			$out .= "Host: ".Config::get('app.domain')."\r\n";
			$out .= "Connection: Close\r\n\r\n";
			fwrite($fp, $out);
			$buffer = '';
			while (!feof($fp)) {
				$buffer .= fgets($fp, 128);
			}
			if(preg_match("/404/m", $buffer)>0) {
				$apacheTests['buan-views']['passed'] = FALSE;
				$apacheTests['buan-views']['resolution'] = "Add the following to your Apache http.conf file: <b>Alias $relativeUrl ".Config::get('core.dir.resources')."</b>";
			}
		}
		fclose($fp);
		$view->apacheTests = $apacheTests;

		// Result
		return $this->wrapper($view);
	}

	/**
	* Generates the core's welcome screen.
	*
	* @param array
	* @return Buan\View
	*/
	public function index($params) {

		// Prepare View
		$view = new View($this);
		$view->setSource(Config::get('core.dir.views').'/core/index.tpl.php');

		// Result
		return $this->wrapper($view);
	}

	/*
	# @method View manual( array $params )
	# $params	= Action parameters
	#
	# Generates a page from the documentation that comes bundled with the system.
	*/
	function manual($params) {

		// View
		$view = new View();

		// TOC
		$toc = array(
			'introduction'=>array(
				'title'=>'Introduction',
				'chapters'=>array(
					'requirements'=>array(
						'title'=>'System requirements',
						'chapters'=>array(
							'phpext'=>array('title'=>'PHP extensions')
						)
					),
					'installation'=>array('title'=>'Installation'),
					'post-installation'=>array(
						'title'=>'Post-installation tasks',
						'chapters'=>array(
							'#app-config'=>array('title'=>'Application configuration variables'),
							'#db-connection'=>array('title'=>'Database connections'),
							'#model-relations'=>array('title'=>'Define Model relationships'),
							'#bootstrap'=>array('title'=>'Bootstrap script')
						)
					),
					'conventions'=>array('title'=>'Conventions'),
					'autoloading'=>array('title'=>'Class autoloading')
				)
			),
			'how-it-works'=>array(
				'title'=>'How it works',
				'chapters'=>array(
					'#urlcommand'=>array(
						'title'=>'The UrlCommand',
						'chapters'=>array(
							'#global-view'=>array('title'=>"The '/global-view' command")
						)
					),
					'#rendering'=>array('title'=>'Rendering a View to output'),
					'global-config'=>array('title'=>'Global configuration')
				)
			),
			'environment'=>array(
				'title'=>'The Buan environment',
				'chapters'=>array(
					'#global-config'=>array('title'=>'Global configuration variables')
				)
			),
			'controller'=>array('title'=>'The Controller'),
			'model'=>array(
				'title'=>'The Model',
				'chapters'=>array(
					'#model-manager'=>array('title'=>'ModelManager'),
					'relationships'=>array('title'=>'Model relationships'),
					'example'=>array('title'=>'Basic example')
				)
			),
			'view'=>array(
				'title'=>'The View',
				'chapters'=>array(
					'#global-view'=>array('title'=>'The GlobalView')
				)
			),
			'extensions'=>array('title'=>'Buan Extensions'),
			'example-app'=>array(
				'title'=>'Example application'
			)
		);
		$view->toc = $toc;

		// Determine requested page
		$chapter = $chapterPath = $previousChapterPath = $nextChapterPath = NULL;
		if(isset($params[0])) {
			$chapters = strpos($params[0], '.')>0 ? explode(".", $params[0]) : array($params[0]);
			$c = array_shift($chapters);
			$chapterPath = $c;
			$chapterChain = array();

			if(isset($toc[$c])) {

				// Find position of chapter within tree
				$chapterIndex = '';
				$count = 1;
				foreach($toc as $k=>$v) {
					if($k==$c) {
						break;
					}
					$count++;
				}
				$chapter = $toc[$c];
				$chapter['__key'] = $c;
				$chapterChain[] = $chapter;
				$chapterIndex = $count;

				// Search through sub-chapters to find target chapter
				while(count($chapters)>0 && $chapter!==NULL) {
					$c = array_shift($chapters);
					$chapterPath .= ".$c";

					if(isset($chapter['chapters'][$c])) {
						$count = 1;
						foreach($chapter['chapters'] as $k=>$v) {
							if($k==$c) break;
							$count++;
						}
						$chapter = $chapter['chapters'][$c];
						$chapter['__key'] = $c;
						$chapterChain[] = $chapter;
						$chapterIndex .= ".{$count}";
					}
					else {
						$chapter = NULL;
					}
				}

				// Work out previous and next chapters
				
			}
		}
		$templateFile = $chapter===NULL ? 'toc.tpl' : 'chapter.'.$chapterPath.'.tpl';
		$view->setSource(Config::get('core.dir.views').'/core/manual/'.$templateFile);

		// Manual wrapper
		$wrapper = new View();
		$wrapper->setSource(Config::get('core.dir.views').'/core/manual/wrapper.tpl');
		$wrapper->attachViewToSlot($view, 'chapter');
		$wrapper->footer = array(
			'previous'=>$chapter===NULL ? NULL : ($previousChapterPath===NULL ? NULL : UrlCommand::createUrl('core', 'manual', $previousChapterPath)),
			'next'=>$chapter===NULL ? NULL : ($nextChapterPath===NULL ? NULL : UrlCommand::createUrl('core', 'manual', $nextChapterPath))
		);
		$wrapper->chapterH1 = $chapter===NULL ? 'Table of Contents' : "{$chapterIndex}. {$chapter['title']}";

		// Result
		return $this->wrapper($wrapper);
	}

	/*
	# @method View extensionManager( array $params )
	# $params	= Action parameters
	#
	# Extension manager.
	*/
	public function extensionManager($params) {

		// Check login
		if(!Core::hasAdminAccess()) {
			return Core::getLoginView($_SERVER['REDIRECT_URL']);
		}

		// View
		$view = new View();
		$view->setSource(Config::get('core.dir.views').'/core/extension-manager.tpl.php');
		$extName = isset($params[0]) ? $params[0] : NULL;
		$method = isset($params[1]) ? $params[1] : NULL;

		// Get all available extensions
		$extensions = Extension::getAvailableList();
		if(!is_null($extName)) {
			$extensions = isset($extensions[$extName]) ? array($extName=>$extensions[$extName]) : array();
		}
		$view->extensions = $extensions;

		// Perform action
		if(!is_null($method)) {

			// no-cache header
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Mon, 01 Jan 1977 00:00:00 GMT");
			header("Content-Type: text/xml");
			$result = '<'.'?xml version="1.0"?'.">\n";

			// Call the defined method, if it exists
			$ext = Extension::getExtensionByName($extName);
			if(method_exists($ext, $method)) {
				if($returnValue = $ext->$method()) {
					$result .= '<result code="0"><message><![CDATA[Success]]></message>';
					$result .= '<returnValue><![CDATA['.json_encode($returnValue).']]></returnValue>';
					$result .= '</result>';
				}
				else {
					$entries = SystemLog::getAll();
					foreach($entries as &$entry) {
						$entry = array('typeString'=>$entry->getTypeString(), 'message'=>$entry->getMessage());
					}
					$result .= '<result code="1"><message><![CDATA[Call to '.get_class($ext).'::'.$method.'() failed.]]></message>';
					$result .= '<returnValue><![CDATA['.json_encode($entries).']]></returnValue>';
					$result .= '</result>';
				}
			}
			else {
				$result .= '<result code="2"><message><![CDATA[Method '.get_class($ext).'::'.$method.'() does not exist.]]></message>';
				$result .= '<returnValue><![CDATA[]]></returnValue>';
				$result .= '</result>';
			}

			// Print result
			print $result;
			Core::shutdown();
		}

		// Result
		return $this->wrapper($view);
	}

	/**
	* Run unit tests.
	*
	* @param array
	* @return Buan\View
	*/
	public function unitTests($params) {

		// Grab the welcome screen template
		$view = new View($this);
		$view->setSource(Config::get('core.dir.views').'/core/unit-tests.tpl.php');

		// Result
		return $this->wrapper($view);
	}

	/**
	* All core actions should pass their view through this method in order wrap
	* some common elements around it.
	*
	* @param Buan\View
	* @return Buan\View
	*/
	private function wrapper($slotView) {

		// Prepare the GlobalView
		// We want to use our own template, overriding the application author's
		// own GlobalViewController.
		// Because we can't guarantee that the author is not caching their GlobalView
		// instance, we'll need to store a reference to it in the $slotView.
		// Any subsequent calls to View::getGlobalView() in child slots should be
		// replaced with $GV
		$GV = View::getGlobalView();
		$GV->setSource(Config::get('core.dir.views').'/global-view/index.tpl.php');
		$GV->html = new HtmlView($GV);
		$slotView->GV = $GV;

		// Create a View for rendering the SystemLog
		$slView = new View();
		$slView->setSource(Config::get('core.dir.views').'/system-log.tpl.php');
		$GV->attachViewToSlot($slView, 'system-log');

		// Result
		return $slotView;
	}
}
?>