<?php
/*
# $Id$
*/

// Dependencies
use Buan\Config;
use Buan\Controller;
use Buan\View;

/*
# @class IndexController
*/
class IndexController extends Controller {

	/*
	# @method View index( array $params )
	# $params	= Action parameters
	#
	# Default index View.
	*/
	function index($params) {

		// Prepare the View
		$view = new View($this);
		$view->setSource(Config::get('core.dir.views').'/index/index.tpl.php');

		// Result
		return $view;
	}
}
?>