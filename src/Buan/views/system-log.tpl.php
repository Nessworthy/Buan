<?php
/*
# $Id: SystemLog.tpl 155 2007-09-17 10:48:30Z james $
*/

use Buan\SystemLog;

$systemLog = SystemLog::getAll();
if(count($systemLog)>0) {
	echo '<ul>';
	foreach($systemLog as $entry) {
		echo '<li>'.strtoupper($entry->getTypeString()).': '.htmlspecialchars($entry->getMessage()).'</li>';
	}
	echo '</ul>';
}
?>