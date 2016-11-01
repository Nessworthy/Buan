<?php
/* $Id$ */
use Buan\UrlCommand;
?>

<h2>Configure php.ini (optional)</h2>
<p>
	The following PHP configuration directives are in fact all optional, but recommended. They can be set in either your global <span class="path">php.ini</span> configuration file, or by making use of the <span class="code">php_flag</span> within an <span class="path">.htaccess</span> file placed in your application's document-root.
</p>
<ul>
	<li>
		<span class="code">short_open_tag = Off</span> (not absolutely necessary, but watch out for occurances of <span class="code">&lt;?</span> in your templates)
	</li>
	<li>
		<span class="code">output_buffering = On</span>
	</li>
	<li>
		<span class="code">error_reporting = E_ALL | E_STRICT</span> (not essential, but recommended to encourage clean code during development)
	</li>
	<li>
		<span class="code">register_globals = Off</span> (you really shouldn't have them on anyway!)
	</li>
</ul>
<p>
	You may also add Buan's <span class="path">buan-core</span> folder to your global <span class="code">include_path</span>, eg:
</p>
<pre class="code-block">include_path = ".;/usr/local/buan/buan-core"	<span class="comment"># on Unix systems</span>
include_path = ".;c:\usr\local\buan\buan-core"	<span class="comment"># on Windows systems</span>
</pre>

<h2>Getting hold of the source files</h2>
<p>
	As of this writing you can download Buan from a couple of locations:
</p>
<ol>
	<li>The subversion repository at <a href="http://svn.thundermonkey.net/jdg/buan/">http://svn.thundermonkey.net/jdg/buan/</a></li>
	<li>A daily snapshot of the subversion trunk at <a href="http://www.thundermonkey.net/builds/buan-trunk.tar.gz">http://www.thundermonkey.net/builds/buan-trunk.tar.gz</a></li>
</ol>

<h2>Setting up the Buan core</h2>
<p>
	Now you've got hold of the source tree, follow these instructions to get the core Buan files copied to the right directories and get Apache setup to access them.
</p>
<ol>
	<li>
		Copy the <span class="path">buan-core</span> folder to any location on your server (preferably to a location that is not directly accessible via a URL) and note down the full path to this location as you will need it later. For the remaining instructions we'll assume you've copied it to:
<pre class="code-block">/usr/local/buan/buan-core
</pre>
		<b>Note:</b> You may like to keep several versions of Buan running on the same server for use across a number of sites. In this case you could use something like <span class="path">/usr/local/buan-1.0.0/buan-core</span>.
	</il>
	<li>
		Ensure that the user account under which Apache is running (commonly <span class="path">www</span>) has full read/execute permissions on <span class="path">buan-core</span> and all files/folders contained within. On Unix systems this can be done with the command:
<pre class="code-block">$ chown -R www:www /usr/local/buan/buan-core
</pre>
	</li>
	<li>
		Add the following line in Apache's configuration file (usually <span class="path">httpd.conf</span>) in either your site's <span class="code">VirtualHost</span> block (to make it applicable to that site only) or in the global scope (to make it applicable to ALL sites on your server):
<pre class="code-block"># Create a URL alias to the buan-pub folder
Alias /buan-pub /usr/local/buan/buan-core/buan-pub</pre>
		This simply creates a URL alias that will point to all the files within the <span class="path">/usr/local/buan/buan-core/buan-pub</span> folder, eg:
<pre class="code-block">http://www.mysite.net/buan-app</pre>
	</li>
	<li>
		Add the following Apache directives (must exist outside of a <span class="code">VirtualHost</span> block):
<pre class="code-block"># Allow public access to buan-pub
&lt;Directory "/usr/local/buan/buan-core/buan-pub"&gt;
  Order allow,deny
  Allow from all
&lt;/Directory&gt;
</pre>
	</li>
</ol>

<h2>Setup your application</h2>
<ol>
	<li>
		You can create any directory layout you wish for your application, but here's a recommended layout for a first-time installation:
<pre class="code-block">/home/my-app
  /config
  /controllers
  /models
    /managers
  /public_html	<span class="comment"># Your document root, it may be named differently on your system</span>
  /views
</pre>
	</li>
	<li>
		Copy all files from the <span class="path">buan-app/config</span> folder into <span class="path">/home/my-app/config</span>.
	</li>
	<li>
		Copy all files from the <span class="path">buan-app/public_html</span> folder into <span class="path">/home/my-app/public_html</span>.
	</li>
	<li>
		If you have <em>not</em> added the <span class="path">buan-core</span> folder to your <span class="code">include_path</span> directive in <span class="path">php.ini</span>, then you will now need to open the gateway script <span class="path">/home/my-app/public_html/index.php</span> in a text-editor and adjust the path in the <span class="code">require(...)</span> command to point to the location of your <span class="path">buan-core</span> folder. ie:
<pre class="code-block">require_once('/usr/local/buan/buan-core/Buan.php');
</pre>
	</li>
	<li>
		Add the following directives to your Apache configuration file:
<pre class="code-block"># Allow scripts in the application's document root to use .htaccess files
&lt;Directory "/home/my-app/public_html"&gt;
  AllowOverride All
&lt;/Directory&gt;
</pre>
	</li>
	<li>
		Load the application in your browser and you should see a welcome message. If not, consult the <a href="<?php echo UrlCommand::createUrl('core', 'manual', 'troubleshooting'); ?>">Troubleshooting</a> section of the manual.
	</li>
</ol>