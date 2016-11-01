<?php
/* $Id$ */
use Buan\UrlCommand;
?>

<p>
	Controllers are the workhorses behind your application. They are simply classes that perform the following function:
</p>
<ul>
	<li>Carry out all logic</li>
	<li>Generate and return a <span class="keyword">View</span> instance</li>
</ul>

<h3>Class location</h3>
<p>
	You must create a Controller class within either the <span class="path">[app.dir.controllers]</span>, <span class="path">[core.dir.controllers]</span> or <span class="path">[ext.*.dir.controllers]</span> folder, name it using the <span class="keyword">UpperCamelCaps</span> convention and always suffix with the word <b>Controller</b>. For example:
</p>
<pre class="code-block">[app.dir.controllers]/HomeController.php
[app.dir.controllers]/AccountRegisterController.php

[core.dir.controllers]/admin/AccountController.php
[core.dir.controllers]/api/public/PeopleController.php
</pre>
<p>
	Note that you can also store Controller classes within sub-folders which are named using the <span class="keyword">lower-hyphenated</span> convention. If you decide to use sub-folders, then remember to create an <span class="path">IndexController.php</span> within that folder to handle requests which don't explicitly specify a Controller.
</p>

<h3>Class structure</h3>
<p>
	The basic structure of a Controller class is as follows:
</p>
<pre class="code-block">&lt;?php
/* Placed in, for example, [app.dir.controllers]/BlogController.php */

class BlogController extends Controller {

	protected $privateMethods = array('query');

	public function index($params) {
		$view = new View();
		$view->setSource('/path/to/a/template.tpl');
		return $view;
	}

	public function unknown($params, $requestedAction) {
		...
	}

	public function findPost($params) {
		...
	}

	public function query($params) {
		...
	}

	private function init() {
		...
	}
}
?&gt;
</pre>
<p>
	In this example we have created the <span class="code">Blog</span> Controller with five actions; <span class="code">index</span>, <span class="code">unknown</span>, <span class="code">findPost</span>, <span class="code">query</span> and <span class="code">init</span>. You can actually create a Controller which contains no actions whatsoever, in which case the special <span class="code">index</span> and <span class="code">unknown</span> actions will be inherited from the <span class="code">Controller</span> class (or your own custom Controller class if applicable).
</p>
<p>
	Note that every method that corresponds to a controller-action must support at least a single argument (<span class="code">$params</span> in our example) which basically holds every parameter passed via the URL, in the same order they appear in the URL.
</p>
<p>
	The <span class="code">$privateMethods</span> class property is entirely optional and is used to tell Buan which of the Controller's public methods must NOT be executed from the URL. In this example the <span class="code">query</span> action is blocked from being called via the URL. You can achieve the same effect by simply defining your class method as <span class="code">private</span>, as we've done for <span class="code">init</span> in our example, but this facility gives you more flexibility if, for example, you wanted your class method to be executable from elsewhere in your application.
</p>
<p>
	If this Controller were to be called from a URL that hasn't specified an action, then Buan would default to executing the <span class="code">index</span> action.
</p>
<p>
	If a non-existent action was called then the call would be passed through to the <span class="code">unknown</span> action and the <span class="code">$requestedAction</span> argument in our example would contain that originally requested action.
</p>
<p>
	The class methods must use the <span class="keyword">lowerCamelCaps</span> naming convention, but in the URL they will map to the equivalent <span class="keyword">lower-hyphenated</span> string as described below.
</p>
<p>
	All actions must return an instance of the <span class="code">View</span> class (or your own custom class if applicable).
</p>

<h3>Mapping a URL to a controller/action</h3>
<p>
	The default url-parsing process in <span class="code">UrlCommand::create()</span> will peform the following basic mapping:
</p>
<pre class="code-block"># Given URL: www.mydomain.com/blog/find-post/joe+bloggs?orderby=date
# Look in all controller folders for <b>BlogController.php</b>
# Execute the <b>findPost()</b> method and pass it the array of parameters, <b>array('joe bloggs')</b>
# Note that the <b>orderby</b> query parameter will be available as normal in <b>$_GET['orderby']</b>
</pre>
<p>
	You may also define your own parsing function/class-method to override this default behaviour. See <a href="<?php echo UrlCommand::createUrl('core', 'manual', 'how-it-works'); ?>">How it works</a> for more details.
</p>