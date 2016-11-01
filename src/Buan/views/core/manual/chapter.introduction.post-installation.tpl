<?php
/* $Id$ */
?>

<h2>Application configuration folder</h2>
<p>
	Your gateway script <span class="path">index.php</span> contains [something like] the following line:
</p>
<pre class="code-block">Core::configure('/home/my-app/config');
</pre>
<p>
	This is simply telling Buan where to find your application's <span class="keyword">configuration folder</span> so that it may prepare the environment using information from the files within. In particular it will look for the following files:
</p>
<ul>
	<li>
		<span class="path">app.php</span> <b><em>(required)</em></b><br/>
		Sets global configuration values in the <span class="path">[app.*]</span> namespace, provides database connection information and defines any required Model relationships.
	</li>
	<li>
		<span class="path">bootstrap.php</span> <em>(optional)</em><br/>
		Contains any custom initialzation-code that you want to execute prior to the dispatch cycle being started.
	</li>
</ul>
<p>
	Let's look at those files in more detail ...
</p>

<h3>app.php</h3>
<a name="app-config"></a>
<h4>Global configuration</h4>
<p>
	Your application can use the <span class="path">[app.*]</span> configuration namespace to set whatever variables you wish. However, certain variables <em>must</em> be defined so that Buan knows where to find certain files and knows how to operate on certain data. These variables are listed below and are defined in your <span class="path">app.php</span> file by using the <span class="code">Config::set()</span> class method:
</p>
<p>
	(<b>NOTE:</b> If you're using the bundled <span class="path">app.php</span> then you may not need to specify values for any of these variables as they are already populated with calculated values.)
</p>
<ul>
	<li>
		<span class="path"><b>app.command.default</b></span><br/>
		If no command has been passed via the URL, then the system will default to this command (in the format <span class="path">/controller-name/action-name/param0/param1/.../paramN</span>)
	</li>
	<li>
		<span class="path"><b>app.command.parameter</b></span><br/>
		Defines the <span class="code">$_REQUEST</span> element in which the URL command will be stored (defaults to <span class="code">do</span>, ie. <span class="code">$_REQUEST['do']</span>)
	</li>
	<li>
		<span class="path"><b>app.command.urlPrefix</b></span><br/>
		If you want to use "clean" URLs (the default behaviour) then set this to an empty string, otherwise it should be the same as <span class="path">[app.command.parameter]</span>.
	</li>
	<li>
		<span class="path"><b>app.dir.controllers</b></span><br/>
		Absolute path to the folder that contains your Controller classes and subfolders.
	</li>
	<li>
		<span class="path"><b>app.dir.models</b></span><br/>
		Absolute path to the folder containing your Model classes.
	</li>
	<li>
		<span class="path"><b>app.dir.modelManagers</b></span><br/>
		Absolute path to the folder containing your ModelManager classes.
	</li>
	<li>
		<span class="path"><b>app.docRoot</b></span><br/>
		Absolute path to your application's public document root folder.
	</li>
	<li>
		<span class="path"><b>app.domain</b></span><br/>
		The fully-qualified domain name under which your application is running (eg. www.my-site.com)
	</li>
	<li>
		<span class="path"><b>app.password</b></span><br/>
		The password used to access the core Buan facilities of your application - <b>change immediately after installation</b>.
	</li>
	<li>
		<span class="path"><b>app.urlRoot</b></span><br/>
		If you store the entire application within a sub-folder in your domain's document-root, then specify that folder path here (relative to the document-root).<br/>
		eg. if application is in "http://www.domain.com/my/app" then urlRoot would be set to "/my/app"
	</li>
</ul>
<p>
	It is strongly recommended that you change the <span class="path">[app.password]</span> setting immediately after installing Buan as it presents a potential security risk to your application. By default, the password is set to <span class="code">admin</span>.
</p>

<p>
	There are also some optional configuration variables that Buan will use where required:
</p>
<ul>
	<li>
		<span class="path"><b>app.dir.extensions</b></span><br/>
		Absolute path to the folder in which you are storing any Buan Extensions.
	</li>
	<li>
		<span class="path"><b>app.dir.ignored</b></span><br/>
		An array of folder names that will be ignored during any folder-iterations done by the core (eg. .svn, cvs, etc)</i>
	</li>
	<li>
		<span class="path"><b>app.dir.temp</b></span><br/>
		Absolute path to a temporary folder. If not specified, but is required by any element, then Buan will attempt to find a default path <em>which may be shared with other users on the server.</em>
	</li>
	<li>
		<span class="path"><b>app.extensions</b></span><br/>
		An array of the names of any Buan Extensions that you want to use in your application. (see <a href="">Buan Extensions</a> for further information)
	</li>
</ul>

<p>
	Finally there are a few special configuration variables that are created at runtime when you call <span class="code">Core::configure()</span>:
</p>
<ul>
	<li>
		<span class="path"><b>app.command.requested</b></span><br/>
		Stores the originally requested command (ie. the value stored in <span class="code">$_REQUEST</span>)
	</li>
	<li>
		<span class="path"><b>app.dir.config</b></span><br/>
		Absolute path to your configuration folder.
	</li>
</ul>

<a name="db-connection"></a>
<h4>Database connection setup</h4>
<p>
	TODO
</p>

<a name="model-relations"></a>
<h4>Model relationship definitions</h4>
<p>
	TODO
</p>

<a name="bootstrap"></a>
<h3>bootstrap.php</h3>
<p>
	Strictly speaking this file is not needed because you could quite easily add any custom initialization routines to the <span class="path">index.php</span> gateway script immediately after <span class="code">Core::configure(...)</span> is called. However, keeping it separately in the configuration folder means you can keep all your environment-initialization logic in one convenient place.
</p>
<p>
	You can put pretty much anything you want in this script. Most common tasks may include:
</p>
<ul>
	<li>Starting/recovering a session</li>
	<li>Manipulating <span class="path">[app.command.requested]</span> prior to entering the dispatch routine.</li>
	<li>Defining custom functions for use elsewhere in the application</li>
	<li>Defining and registering a custom dispatch routine (see <a href="">The dispatch routine</a> for more information)</li>
	<li>Adding custom paths to the <span class="code">BuanAutoLoader</span> classpath</li>
</ul>