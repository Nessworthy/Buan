<?php
/**
# $Id$
#
# Override this with your own application's "GlobalView" controller.
*/

// Dependencies
use Buan\Config;
use Buan\Controller;
use Buan\View;

// Class
class GlobalViewController extends Controller {

	/**
	* Returns the "Global View", which is the View object that sits above all
	* others in the View hierarchy.
	*
	* @param array
	* @return Buan\View
	*/
	public function index($params) {

		// Create the View
		$view = new View();
		$view->setSource(Config::get('core.dir.views').'/global-view/index.tpl.php');

		// Return
		return $view;
	}
}
?>