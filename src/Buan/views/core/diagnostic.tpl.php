<?php
/* $Id: diagnostic.tpl 155 2007-09-17 10:48:30Z james $ */
use Buan\Config;

$GV->html->addStylesheets(Config::get('core.url.resources').'/css/core/diagnostic.css');
?>

<h1>Diagnostics</h1>

<h3>Folder path/permission checks:</h3>
<ul class="test">
<?php foreach($permissionDirs as $dir): ?>
	<li>
		<?php echo $dir['name']; ?> ... <?php echo $dir['passed'] ? '<span class="passed">OK</span>' : '<span class="failed">FAIL</span> ('.$dir['testMessage'].')'; ?>
<pre>
<?php echo $dir['path']; ?>
</pre>
	</li>
<?php endforeach; ?>
</ul>

<h3>PHP configuration</h3>
<ul class="test">
<?php foreach($phpConfigs as $cfg): ?>
	<li>
		<?php echo htmlspecialchars($cfg['name']); ?> ... <?php echo $cfg['passed'] ? '<span class="passed">OK</span>' : '<span class="failed">FAIL</span> ('.$cfg['actual'].')'; ?>
	</li>
<?php endforeach; ?>
</ul>

<h3>Apache configuration</h3>
<ul class="test">
<?php foreach($apacheTests as $test): ?>
	<li>
		<?php echo htmlspecialchars($test['name']); ?> ... <?php echo $test['passed'] ? '<span class="passed">OK</span>' : '<span class="failed">FAIL</span>'; ?>
		<?php if(!$test['passed']): ?>
<pre>
<?php echo $test['resolution']; ?>
</pre>
		<?php endif; ?>
	</li>
<?php endforeach; ?>
</ul>