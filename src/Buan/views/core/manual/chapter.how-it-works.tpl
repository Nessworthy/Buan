<?php
/* $Id$ */
use Buan\UrlCommand;
?>

<p>
	The general execution process from start to finish can be summed up in the following steps:
</p>
<ol>
	<li>Parse a given URL into a <span class="keyword">UrlCommand</span> containing the name of a <span class="keyword">Controller</span>, the name of an <span class="keyword">Action</span> and zero or more parameters.</li>
	<li>Execute the UrlCommand which loads the required Controller class and executes the appropriate Action method within that Controller. This returns a <span class="keyword">View</span> object.</li>
	<li>Attach the View to the <span class="keyword">GlobalView</span>.</li>
	<li>Render the GlobalView.</li>
</ol>
<p>
	The bundled <span class="path">index.php</span> gateway script includes a call to <span class="code">Core::boot()</span> which will take care of this entire process for you, but we'll describe the various stages and elements in more detai below so you can gather a better idea of how Buan actually operates.
</p>

<a name="urlcommand"></a>
<h2>The UrlCommand</h2>
<p>
	All the functional elements that make up Buan are brought together by the concept of the <span class="keyword">UrlCommand</span>, which is simply an object representation of a requested URL.
</p>
<p>
	A UrlCommand consists of three key components:
</p>
<ul>
	<li>A <span class="keyword">Controller</span></li>
	<li>An <span class="keyword">Action</span>, and</li>
	<li>Zero or more parameters</li>
</ul>
<p>
	The process behind how a UrlCommand is generated from a simple string URL is handled by a URL-parsing routine within the <span class="code">UrlCommand::create()</span> method which is called as follows:
</p>
<pre class="code-block">// Given the URL: http://www.my-domain.net/people/list/bob/full-details
$command = UrlCommand::create('/people/list/bob/full-details');
</pre>

<h3>The default URL-parsing routine</h3>
<p>
	Continuing with our example URL above, the default parsing routine will break it down in the following elements:
</p>
<pre class="code-block">Controller: 'people'
Action: 'list'
Parameters: array(0=>'bob', 1=>'full-details')</pre>
<p>
	Next, it finds a suitable Controller class file; <span class="path">PeopleController.php</span> in this case. The following locations are searched to find this file (the order of which is significant):
</p>
<ul>
	<li><span class="path">[app.dir.controllers]</span></li>
	<li><span class="path">[core.dir.controllers]</span></li>
	<li><span class="path">[ext.*.dir.controllers]</span> (all installed extensions)</li>
</ul>
<p>
	If the file is found then an instance of that Controller class is created and stored in the UrlCommand.
</p>
<p>
	However, if the file is not found then <b>people</b> is treated as a sub-folder in which a search is carried out for a Controller class file based on the name of the next element in the URL, ie. <span class="path">ListController.php</span>.
</p>
<p>
	This process will continue until either a suitable Controller class has been found or all searches have been exhausted and no Controller class was found. If the latter then Buan will use the special Controller, <span class="code">UnknownController</span>. This Controller is available in <span class="path">[core.dir.controllers]</span>, but can be easily overridden by creating your own <span class="path">[app.dir.controllers]/UnknownController.php</span> class.
</p>

<h3>Writing a custom URL-parsing routine</h3>
<p>
	If you would prefer not to use the default parsing routine described above, then you can very easily write your own function/class-method to handle it exactly as you need. To do this you must register your custom function/class as follows (this would most likely be added to your <span class="path">[app.dir.config]/bootstrap.php</span> file):
</p>
<pre class="code-block">// Function that parses URLs
function myParser($url) {
	// ... parse URL and generate a valid UrlCommand instance ...
	return $urlCommandInstance;
}

// Register the function to override the default behaviour
UrlCommand::registerUrlParser('myParser');
</pre>
<p>
	The function (or class method) that you register via <span class="code">UrlCommand::registerUrlParser()</span> must accept a single argument, the URL, and must return a <span class="code">UrlCommand</span> instance which will use an existing, valid Controller class.
</p>

<h3>Executing the command</h3>
<p>
	Once a valid UrlCommand instance has been created, it can be executed.
</p>
<p>
	Assuming <span class="path">PeopleController.php</span> was found in our example URL, the next step is to execute the <span class="keyword">Action</span> part of the URI, ie. <b>list</b>. This should simply be a public method in the <span class="code">PeopleController</span> class, but rather than calling the method directly it is actually executed via the <span class="code">Controller::invokeAction()</span> method as follows:
</p>
<pre class="code-block">$c = new PeopleController(array(0=>'bob', 1=>'full-details'));
$view = $c->invokeAction('list');</pre>
<p>
	All Controller methods take a single argument which is an array containing all the parameters that were passed in the URL. This is an integer-indexed array and the order of the parameters within it match the order in which they were passed in the URL. Any other named parameters (ie. those in the query portion of the URL) are not included in this array, but can be accessed as normal from <span class="code">$_GET</span>, <span class="code">$_POST</span>, etc.
</p>
<p>
	Note that all Controller Actions <em>must</em> return a <span class="keyword">View</span> object (or a class that extends the core <span class="code">View</span> class.)
</p>

<a name="global-view"></a>
<h2>The '/global-view' command</h2>
<p>
	This is a special command that will result in a View that represents the <span class="keyword">GlobalView</span> - a kind of wrapper template (see <a href="">GlobalView</a> for more details.)
</p>

<a name="rendering"></a>
<h2>Rendering a View to output</h2>
<p>
	The resulting <span class="code">$view</span> object returned by a Controller is attached to the <span class="code">'action'</span> slot within the <span class="keyword">GlobalView</span> (a special View which we'll look at later) and finally rendered to output:
</p>
<pre class="code-block">$GView = View::getGlobalView();
$GView->attachViewToSlot($view, 'action');
echo $GView->render();
exit();</pre>