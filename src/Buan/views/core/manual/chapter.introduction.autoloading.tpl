<?php
/* $Id$ */
?>

<p>
	Buan uses the SPL's <a href="http://www.php.net/spl_autoload_register"><span class="code">spl_autoload_register()</span></a> function in order to autoload your application's classes. This has the major benefit of allowing you to register your own autoload functions in addition to Buan's without them overriding each other.
</p>

<h2>Using Buan's Autoloader</h2>
<p>
	Buan core class loader is a simple search-and-load affair which may very well be suitable for your own auto-loading requirements.
</p>
<pre class="code-block">&lt;?php
BuanAutoLoader::addClassPath('/home/my-app/lib/my-classes');

$A = new MyMintClass();
?&gt;
</pre>