<?php
/**
* @package UnitTest
*/

// Setup the Buan environment
require('../application/buan/Buan.php');
Buan\Core::configure(dirname(__FILE__).'/config/app.php', dirname(__FILE__).'/config/bootstrap.php');

// Create and run test suite
$_t = microtime(TRUE);
$suite = new TestSuite();
$suite->addScriptsFolder(dirname(__FILE__).'/tests');
$suite->start();
echo "\n\nTests completed in ".(round(microtime(TRUE)-$_t, 5))." s\n\n";

// Exit with correct code
$logs = Buan\SystemLog::getAll();
foreach($logs as $l) {
	print $l->getMessage()."\n";
}
exit($suite->hasFailures() ? 1 : 0);
?>