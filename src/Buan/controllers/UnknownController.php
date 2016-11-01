<?php
/*
# $Id$
*/

// Dependencies
use Buan\Config;
use Buan\Controller;
use Buan\View;

/*
# @class UnknownController
*/
class UnknownController extends Controller {

	/*
	# @method View index( array $params )
	# $params	= Action parameters
	#
	# Unknown controller.
	*/
	function index($params) {

		// Prepare the View
		$view = new View();
		$view->setSource(Config::get('core.dir.views').'/unknown/index.tpl.php');

		// Result
		return $view;
	}

	/*
	# @method View unknown( array $params )
	# $params	= Action parameters
	#
	# Unknown controller and unknow action.
	*/
	function unknown($params, $actionCommand) {

		// Prepare the View
		$view = new View();
		$view->setSource(Config::get('core.dir.views').'/unknown/unknown.tpl.php');

		// Result
		return $view;
	}
}
?>