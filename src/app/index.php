<?php
use \Buan\Core;

/* Adjust these variables if you're used custom locations for "app" or "buan" */
$__appPath = dirname(__FILE__).'/app';
$__buanPath = dirname(__FILE__).'/buan';

$config = new \Buan\Config("{$__appPath}/config.php");

$core = new Core($config, "{$__appPath}/bootstrap.php");
unset($__appPath, $__buanPath);

$core->boot();

$core->shutdown();