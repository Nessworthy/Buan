<?php
/**
* @package Buan
*/
namespace Buan;
class Core {

	/**
	* This method simply includes the given configuration files, which prepare the
	* application environment, and sets a few of it's own configuration variables.
	*
	* The first script, $configFile, should only perform the following tasks:
	* - Define settings via Config::set()
	* - Optionally define database connections and model relationships
	*
	* The second scripts, $bootstrap, is then free to do anything it wants.
	*
	* For a full list of required and optional config variable, see the bundled
	* "application/app/config.php"
	*
	* @param string Absolute path to the configuration file
	* @param string Absolute path to the bootstrap script
	* @return void
	*/
	static public function configure($configFile, $bootstrap=NULL) {

		// Include configuration file
		if(include($configFile)) {

			// Check for existence of some required settings and set defaults or halt
			$app = Config::get('app');
			if(!isset($app['docRoot'])) {
				SystemLog::add("You have not defined your application's document root in {$configFile}.", SystemLog::FATAL);
				Core::halt();
			}
			if(!isset($app['command']['urlPrefix'])) {
				$app['command']['urlPrefix'] = '';
			}
			if(!isset($app['command']['parameter']) || $app['command']['parameter']==='') {
				$app['command']['parameter'] = 'do';
			}
			if(!isset($app['command']['default'])) {
				$app['command']['default'] = '';
			}
			if(!isset($app['dir']['ignored'])) {
				$app['dir']['ignored'] = array('.', '..', '.svn', 'cvs');
			}
			if(!isset($app['domain'])) {
				$app['domain'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
			}
			if(!isset($app['password'])) {
				$app['password'] = rand(1000000, 9999999);
			}
			if(!isset($app['urlRoot'])) {
				$app['urlRoot'] = '';
			}
			else if($app['urlRoot']!=='') {
				$app['urlRoot'] = preg_replace("/^\/*/", "/", preg_replace("/\/+$/", "", $app['urlRoot']));
			}

			// Reassign filtered settings to Config
			Config::set('app', $app);

			// Ensure that any encoded forward slashes in the command URL remain
			// encoded
			if(isset($_SERVER['QUERY_STRING']) && preg_match("/".$app['command']['parameter']."=([^&]*\%2F[^&]*)/i", $_SERVER['QUERY_STRING'], $m)>0) {
				$_REQUEST[$app['command']['parameter']] = $_GET[$app['command']['parameter']] = $m[1];
			}

			// Store the requested command
			$cp = Config::get('app.command.parameter');
			if(isset($_REQUEST[$cp])) {
				Config::set('app.command.requested', isset($_REQUEST[$cp]) ? $_REQUEST[$cp] : '');
			}
			else if(isset($_SERVER['REQUEST_URI']) && preg_match("/^".preg_quote($app['urlRoot'], '/')."\/index\.php\/(.+)$/i", $_SERVER['REQUEST_URI'], $m)) {
				Config::set('app.command.requested', $m[1]);
			}
			else {
				Config::set('app.command.requested', '');
			}
		}
		else {
			// Log and halt
			SystemLog::add("Cannot find an application configuration file (looking for {$configFile})", SystemLog::FATAL);
			Core::halt();
		}

		// Store the given application configuration file in the global Config so it
		// may be retrieved by other processes if needs be.
		Config::set('app.configFile', $configFile);

		// Include bootstrap script
		if($bootstrap!==NULL && include($bootstrap)) {
			// Success
		}
	}

	/**
	* Performs the following tasks:
	* 1. Generates a UrlCommand instance from the given $urlPath
	* 2. Executes the UrlCommand and attaches the resulting View to the GlobalView
	* 3. Renders the GlobalView to output.
	*
	* @param string The URL to boot
	* @return void
	*/
	static public function boot($urlPath=NULL) {

		// Vars
		if($urlPath===NULL || $urlPath=='') {
			$cr = Config::get('app.command.requested');
			$urlPath = $cr=='' ? Config::get('app.command.default') : $cr;
		}

		// Create and execute the command then attach the resulting View to the
		// GlobalView's "action" slot.
		$command = UrlCommand::create($urlPath);
		$commandView = $command->execute();
		$globalView = View::getGlobalView();
		$globalView->attachViewToSlot($commandView, 'action');

		// Render the GlobalView
		echo $globalView->render();
	}

	/**
	* Stops the system in it tracks and renders whatever Views have been added to
	* the queue.
	*
	* @todo If any calls to Core::halt() occur within the rendering code or
	* templates then recursion will appear. How do we get around that? Set a
	* static var to ensure this is called only once?
	*
	* @return void
	*/
	static public function halt() {

		// Render and display the GlobalView (if not already done so)
		$globalView = View::getGlobalView();
		echo $globalView->render();

		// Exit
		self::shutdown();
	}

	/**
	* Deinitialises the application, generally cleaning up.
	*
	* @return void
	*/
	static public function shutdown() {

		// Close all DB connections

		// Close session

		// Run an application's custom "shutdown" script

		// Stop and exit the script execution
		exit();
	}

	/**
	* Determines if the user has logged in using the application password.
	* If not then a common reaction would be to build the application login
	* interface using:
	*	Core::getLoginView()
	*
	* @return bool
	*/
	static public function hasAdminAccess() {

		// Start session if $_SESSION is not yet available
		$doCloseSession = FALSE;
		if(!isset($_SESSION)) {
			session_start();
			$doCloseSession = TRUE;
		}

		// Find appropriate flag in session
		if(isset($_SESSION['buan']['hasAdminAccess']) && $_SESSION['buan']['hasAdminAccess']==TRUE) {
			$hasAdminAccess = TRUE;
		}
		else {
			$hasAdminAccess = FALSE;
		}

		// Close session, if it was started in this method
		if($doCloseSession) {
			session_write_close();
			unset($_SESSION);
		}

		// Result
		return $hasAdminAccess;
	}

	/**
	* Sets whether or not the user has Buan admin access to the application.
	*
	* @param bool The access flag to set
	*/
	static public function setAdminAccess($state=FALSE) {

		// Determine if a session is already open, otherwise start/resume one.
		$doCloseSession = FALSE;
		if(!isset($_SESSION)) {
			session_start();
			$doCloseSession = TRUE;
		}

		// Set flag
		$_SESSION['buan']['hasAdminAccess'] = $state;

		// Close session, if it was opened in this method
		if($doCloseSession) {
			session_write_close();
			unset($_SESSION);
		}
	}

	/**
	* Returns the application login View (/core/app-login) so the user can
	* potentially gain core admin access.
	*
	* @param string After a successful login, the user will be redirected here
	* @return Buan\View
	*/
	static public function getLoginView($refererUrl=NULL) {

		// Create the view
		$command = UrlCommand::create('/core/app-login');
		$view = $command->execute();
		$view->refererUrl = $refererUrl;

		// Result
		return $view;
	}
}
?>