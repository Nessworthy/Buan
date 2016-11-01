<?php
/*
# $Id$
#
# Initialises the core system by performing the following tasks:
#	- Include all necessary libraries
#	- Prepare global functions and variables
#
# It does NOT operate on any passed parameters (in particular, $_REQUEST['do'])
# - this is left to the application in order to provide as much freedom from the
# core as possible. The core is only here to provide the code framework, not
# overly-enforce routines on the programmer.
*/

/*
# Set some global configuration values.
# Try to work out the url on which the app is running.
*/
use Buan\Config as Config;

/*
# Enable output buffering (if it's not too late!)
*/
$ini_ob = (int) ini_get('output_buffering');
if($ini_ob <= 0) {
	ob_start();
}

/*
# Setup the class auto-loader and other common classes
*/
$cwd = str_replace("\\", "/", dirname(__FILE__));


$dr = isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT']) ? str_replace("\\", "/", $_SERVER['DOCUMENT_ROOT']) : dirname($cwd);
$cwu = strpos(dirname($cwd), $dr)!==FALSE ? str_replace($dr, '', dirname($cwd)) : '';

Config::set('timeStarted', microtime(TRUE));
Config::set('core', array(
	/* Custom URL routes */
	'customUrlRoutes'=>array(),

	/* Directory and URL locations */
	'dir' => array(
		'controllers' => $cwd.'/controllers',
		'resources' => $cwd.'/buan-pub',
		'views' => $cwd.'/views'
	),
	'docRoot' => $cwd,
	'url' => array(
		'resources'=>"{$cwu}/buan/buan-pub"	// Absolute URL to public resources folder
	),

	/* Version information */
	'version'=>array(
		'major' => 1,
		'minor' => 1,
		'revision' => 0
	)
));

/*
# Clean up
*/
unset($cwd, $dr, $cwu);