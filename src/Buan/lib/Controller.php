<?php
/**
* @package Buan
*/
namespace Buan;
use \ReflectionClass;
class Controller {

	/**
	* Stores the name of this controller (all lowercased, underscored version)
	*
	* @var string
	*/
	protected $name = null;

	/**
	* Parameters that have been passed to this controller
	*
	* @var array
	*/
	protected $params = array();

	/**
	# A list of method names that must NOT be called from a URL command.
	# If you want to add any methods to this list, simply create a $privateMethods
	# class property in your sub-class. The methods you list there will
	# automatically be added to this core list.
	#
	# Alternatively, you can prevent methods being invoked by making them "private"
	# rather than "public". However if for some reason you want to call a method
	# from outside of the controller class, then you'd need to use this property.
	*
	* @var array
	*/
	protected $allPrivateMethods = array('__call', 'invokeAction');

	/**
	* Constructor.
	*
	* @param array An array of parameters (order is significant)
	* @return void
	*/
	public function __construct($params=NULL) {

		// Store controller name
		$this->name = Inflector::controllerClass_controllerCommand(get_class($this));

		// Store parameters
		if(func_num_args()>0) $this->params = $params;

		// If it exists in the sub-class, merge $privateMethods into the base class' $allPrivateMethods list
		if(isset($this->privateMethods)) {
			$this->allPrivateMethods = array_merge($this->allPrivateMethods, $this->privateMethods);
		}
	}

	/**
	* This is the default version of the 'index' action (triggered when no action
	* is specified in the url command).
	* Your controllers should override this to handle it to suit your needs.
	*
	* @param array Url parameters
	* @return Buan\View
	*/
	public function index($params) {

		// Log
		SystemLog::add(get_class($this)."::index() invoked by core.", SystemLog::CORE);
	}

	/**
	* This is the default version of the 'unknown' action (triggered when an
	* unknown action is requested in the url command).
	* Your controllers should override this to handle it to suit your needs.
	*
	* @param array Url parameters
	* @param string The originally requested action
	* @return Buan\View
	*/
	public function unknown( $params, $actionCommand ) {

		// Generate the default 404 page
		$view = new View();
		$view->setSource(Config::get('core.dir.views').'/errorPages/404.tpl');

		// Result
		return $view;
	}

	/**
	* This is used as a "catch all" for unknown action calls and routes them
	* through the ::invokeAction method.
	*
	* @param string The method being requested
	* @param array Method arguments
	* @return mixed
	*/
	public function __call($methodName, $params) {

		// Treat the request method as an action call
		$this->invokeAction($methodName);
	}

	/**
	* @param string Action to invoke (lower-hyphenated format, ie. action-command)
	* @return Buan\View
	*/
	final public function invokeAction($actionCommand) {
        
		// Convert the action name to the format used for class method names
		// (ie. ActionName)
		$actionMethodName = Inflector::actionCommand_actionMethod($actionCommand);
        
		// Disregard this invocation if the $actionMethodName is listed in the
		// $allPrivateMethods array
		if(in_array($actionMethodName, $this->allPrivateMethods)) {
			return $this->unknown($this->params, $actionMethodName);
		}
        
		// Invoke the method (ensuring it's "public"), or the 'unknown' method
		// if it doesn't exist
		if(method_exists($this, $actionMethodName)) {
			$r = new ReflectionClass($this);
			$m = $r->getMethod($actionMethodName);
			if(!$m->isPublic() || $m->getName()!==$actionMethodName) {
				SystemLog::add(array('Attempting to call a non-public action method: %s', $actionMethodName), SystemLog::FATAL);
				return new View();
			}
			else {
				return $this->$actionMethodName($this->params);
			}
		}
		else {
			return $this->unknown($this->params, $actionCommand);
		}
	}
}
?>