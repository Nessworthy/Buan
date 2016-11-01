<?php
/* $Id$ */
?>

<p>
	In this section we'll describe how to setup a new music-sharing application from scratch. We'll assume that Buan has already been installed and will use the following folder locations:
</p>
<pre class="code-block"># Buan installation folder
/usr/local/buan

# Application folder
/home/me

# Application's document root
/home/me/public_html
</pre>
<p>
	And we want the following features:
</p>
<ul>
	<li>Clean URLs without a prefix (the default behaviour)</li>
	<li>The application should be run entirely within a sub-folder ("eg-app") on the domain</li>
	<li>A MySQL database will be used for persistent storage of Models</li>
</ul>

<h2>5.1 Directory layout</h2>
<pre class="code-block">/home/me
	/config
	/controllers
	/models
		/managers
	/public_html
		/eg-app
			/css
			/img
			/js
	/views
	
</pre>

<h2>5.2 Configuration</h2>
<pre class="code-block">&lt;?php
<span class="comment">/* /home/me/config/app.php */</span>

<span class="comment">/* Global config*/ </span>
Config::set('app.command.urlPrefix', '');	<span class="comment">// Empty for our uber-clean URLs</span>
Config::set('app.command.parameter', 'do');
Config::set('app.command.default', '/home');
Config::set('app.dir.controllers', dirname(dirname(__FILE__)).'/controllers');
Config::set('app.dir.models', dirname(dirname(__FILE__)).'/models');
Config::set('app.dir.modelManagers', dirname(dirname(__FILE__)).'/models/managers');
Config::set('app.dir.extensions', dirname(dirname(__FILE__)).'/extensions');
Config::set('app.dir.ignored', array('.', '..', '.svn', 'cvs'));
Config::set('app.docRoot', dirname(dirname(__FILE__)));
Config::set('app.domain', "www.my-domain.net");
Config::set('app.extensions', array());
Config::set('app.password', 'a-secure-password');
Config::set('app.urlRoot', '/eg-app');		<span class="comment">// Name of the sub-folder in which the application will run</span>

<span class="comment">/* Database */</span>
Database::addConnection(array(
	'name'=>'default',
	'driver'=>'mysql',
	'host'=>'localhost',
	'database'=>'eg_app',
	'username'=>'root',
	'password'=>''
));

<span class="comment">/* Model relationships */</span>
ModelRelation::define();
?&gt;
</pre>

<h2>5.3 Bootstrap script</h2>