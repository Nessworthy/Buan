<?php
/* $Id$ */
?>

<h1>2.3 The View</h1>
<p>
	A <span class="keyword">View</span> is what the end user actually sees once the requested Controller-action has completed execution. Every Controller-action must return a valid View instance.
</p>
<p>
	The default <span class="code">View</span> class provided by Buan uses PHP scripts as the source for each individual View template. However, if you prefer to use other template engines then you can easily extend this base class to incorporate your own functionality.
</p>

<h2>2.3.1 Anatomy of a View</h2>
<p>
	You can think of a View as equivalent to a node within a simple tree structure.
</p>
<p>
	Right at the top of this tree sits the special <span class="keyword">GlobalView</span>, with each of it's children stored in <span class="keyword">slots</span> below it. The only special slot that you need to be aware of is the <b>action</b> slot within the GlobalView - this is where the resulting View from the Controller-action is stored.
</p>
<p>
	In a typical HTML document the source of the GlobalView template might look like this:
</p>
<pre class="code-block"><?php echo htmlspecialchars("<html>\n<body>\n\t<div id=\"header\">\n\t\tOur Website\n\t</div>\n\t<div id=\"main\">\n\t\t<?php echo \$this->getSlot('action')->render(); ?>\n\t</div>\n</body>\n</html>"); ?>
</pre>

<a name="global-view"></a>
<h2>2.3.2 The GlobalView</h2>
<p>
	The GlobalView is a singleton instance of the <span class="code">View</span> class which can be accessed from anywhere using:
</p>
<pre class="code-block">$globalView = View::getGlobalView();</pre>
<p>
	Calling this method will actually create and execute the UrlCommand <span class="path">/global-view/index</span>. Therefore, to create your own GlobalView you simply need to create a <span class="code">GlobalView</span> Controller with a single <span class="code">index()</span> method which needs to return a <span class="code">View</span> instance. For example:
</p>
<pre class="code-block">/* GlobalViewController.php */

class GlobalViewController extends Controller {
	public function index($params) {
		$view = new View();
		// ... construct View as required ...
		return $view;
	}
}</pre>
<p>
	And because you have full control over this Controller-action you also have the means to use your own custom Controller and View classes if required.
</p>