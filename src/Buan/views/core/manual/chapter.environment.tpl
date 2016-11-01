<?php
/* $Id$ */
use Buan\UrlCommand;
?>

<p>
	The primary point of entry to your application is via the <span class="path">index.php</span> gateway script. This contains 4 stages (or <span class="keyword">runlevels</span>) at which the Buan environment is affected in some way:
</p>
<pre class="code-block">&lt;?php
/* Runlevel 0 */
require('/usr/local/buan/buan-core/init.php');

/* Runlevel 1 */
Core::configure(dirname(dirname(__FILE__)).'/config');

/* Runlevel 2 */
Core::boot();

/* Runlevel 3 */
Core::shutdown();
?&gt;
</pre>

<p>
	The following sections will describe what's available to you in the Buan environment at each runlevel.
</p>

<a name="global-config"></a>
<h2>Global configuration variables</h2>
<p>
	At <span class="keyword">runlevel 0</span>:
</p>
<ul>
	<li>Nothing</li>
</ul>

<p>
	At <span class="keyword">runlevel 1</span>:
</p>
<ul>
	<li>
		<span class="path">core.dir.controllers</span><br/>
		Absolute path to the folder that contains all core Controller classes.<br/>
		All of these Controllers can be overridden by controller classes in your application.
	</li>
	<li>
		<span class="path">core.dir.extensions</span><br/>
		Absolute path to the folder that contains all core Buan extensions.<br/>
	</li>
	<li>
		<span class="path">core.dir.models</span><br/>
		Absolute path to the folder containing all core Model classes.
	</li>
	<li>
		<span class="path">core.dir.modelManagers</span><br/>
		Absolute path to the folder containing all core ModelManager classes.
	</li>
	<li>
		<span class="path">core.dir.resources</span><br/>
		Absolute path to the folder containing publicly accessible resource files (ie. <span class="path">.../buan-pub</span>)
	</li>
	<li>
		<span class="path">core.dir.views</span><br/>
		Absolute path to the folder containing all core View templates.
	</li>
	<li>
		<span class="path">core.docRoot</span><br/>
		Absolute path to the <span class="path">buan-core</span> folder.
	</li>
	<li>
		<span class="path">core.url.resources</span><br/>
		Absolute URL (excluding domain) to the <span class="path">buan-pub</span> resources folder (ie. the Alias you setup in Apache)
	</li>
	<li>
		<span class="path">timeStarted</span><br/>
		The approximate time at which Buan was first initialised (floating-point timestamp).
	</li>
</ul>

<p>
	At <span class="keyword">runlevel 2</span> and <span class="keyword">runlevel 3</span>, all above plus the following (all of which have <a href="<?php echo UrlCommand::createUrl('core', 'manual', 'introduction.post-installation'); ?>#app-config">more detailed explanations</a>):
</p>
<ul>
	<li>
		<span class="path">app.command.default</span>
	</li>
	<li>
		<span class="path">app.command.parameter</span>
	</li>
	<li>
		<span class="path">app.command.requested</span>
	</li>
	<li>
		<span class="path">app.command.urlPrefix</span>
	</li>
	<li>
		<span class="path">app.dir.config</span>
	</li>
	<li>
		<span class="path">app.dir.controllers</span>
	</li>
	<li>
		<span class="path">app.dir.extensions</span>
	</li>
	<li>
		<span class="path">app.dir.ignored</span>
	</li>
	<li>
		<span class="path">app.dir.models</span>
	</li>
	<li>
		<span class="path">app.dir.modelManagers</span>
	</li>
	<li>
		<span class="path">app.dir.temp</span>
	</li>
	<li>
		<span class="path">app.docRoot</span>
	</li>
	<li>
		<span class="path">app.domain</span>
	</li>
	<li>
		<span class="path">app.password</span>
	</li>
	<li>
		<span class="path">app.urlRoot</span>
	</li>
</ul>