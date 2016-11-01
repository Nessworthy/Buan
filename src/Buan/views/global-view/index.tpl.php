<?php
/* $Id$ */
use Buan\Config;
use Buan\ModelTracker;
use Buan\UrlCommand;

$url = Config::get('core.url.resources');
$this->getView()->addStylesheets('screen', "{$url}/css/global-view.css", "{$url}/css/core/BuanCore.css");
$this->getView()->addJavascripts("{$url}/js/core/BuanCore.js");
unset($url);
?>
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Buan</title>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="Buan PHP Framework" />

	<?php 
	/* External stylesheets */
	echo $this->getView()->getStylesheetsView()->renderAfter($this->getView());
	?>
</head>
<body>
	<div id="GlobalHeader">
		<h1>Buan</h1>
		<ul>
			<li>&raquo; <a href="<?php echo UrlCommand::createUrl('core'); ?>">Core</a> &laquo;</li>
		</ul>
	</div>

	<div id="EventLogOutput">
		<?php echo $this->getView()->getSlot('system-log')->renderAfter($this->getView()); ?>
	</div>

	<div id="ActionOutput">
		<div id="buancore">
			<ul id="buancore-menu">
				<li><a href="http://<?php echo UrlCommand::createAbsoluteUrl('core'); ?>" title="Welcome">Welcome</a></li>
				<li><a href="http://<?php echo UrlCommand::createAbsoluteUrl('core', 'manual'); ?>" title="Manual">Manual</a></li>
				<?php /*<li><a href="">Model Schema Generator</a></li>
				<li><a href="">Model Schema Diff</a></li>
				<li><a href="">Report a Bug</a></li> */ ?>
				<li><a href="http://<?php echo UrlCommand::createAbsoluteUrl('core', 'extension-manager'); ?>" title="Extension Manager">Extension Manager</a></li>
				<li><a href="http://<?php echo UrlCommand::createAbsoluteUrl('core', 'diagnostic'); ?>" title="Diagnostics">Diagnostics</a></li>
				<li><a href="http://<?php echo UrlCommand::createAbsoluteUrl('core', 'unit-tests'); ?>" title="Diagnostics">Unit Tests</a></li>
			</ul>
			<div id="buancore-main">
				<?php echo $this->getView()->getSlot('action')->render(); ?>
			</div>
		</div>
	</div>

	<div id="GlobalFooter">
		Buan &#169; James Gauld |
		Page generated in approx. <?php echo round(microtime(TRUE)-Config::get('timeStarted'), 3); ?> seconds
		and <?php echo number_format(memory_get_peak_usage()/(1024*1024), 2); ?> MB memory)
	</div>

<?php
/* Add external Javascripts */
include_once(Config::get('core.dir.resources').'/js/Buan.js.php');
echo $this->getView()->getJavascriptsView()->render();
?>
</body>
</html>