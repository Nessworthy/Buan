<?php
/* $Id$ */
?>

<p>
	The core class <span class="code">Config</span> enables you to keep all your global configuration variables in one place which is accessible from anywhere within your application. It's syntax is very basic:
</p>
<pre class="code-block">// Write some configuration variables
Config::set('app.dir.cache', '/path/to/my/cache/doler');

// Read values
Config::get('app.dir.cache');
Config::get('app.dir');		/* returns all values in [app.dir] as ahash */
</pre>

<h3>Variable namespaces</h3>
<p>
	You will have noticed that access to configuration variables is by means of a dot-separated namespace convention.
</p>
<p>
	You may use whatever namespaces you want, but must be aware of the three reserved namespaces; <span class="path">[app.*]</span> (all settings related to your application), <span class="path">[core.*]</span> (all settings related to the Buan core) and <span class="path">[ext.*]</span> (all settings related to any Buan Extensions you have installed).
</p>