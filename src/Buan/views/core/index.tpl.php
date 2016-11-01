<?php
/* $Id: welcome.tpl 155 2007-09-17 10:48:30Z james $ */
?>

<h1>Welcome, to the core</h1>
<p>
	From here you can manage various components of your Buan-based application.
</p>
<p>
	Some interfaces, such as this welcome screen, are public (anyone can view them) whilst others are password protected (you can find your application password in the <span class="path">/path/to/config/app.php</span> file.)
</p>

<h3>Want to hide these core interfaces?</h3>
<p>
	Although there's no real danger in leaving these core interfaces accessible to the public (those that can potentially do any harm are password protected anyway), you do have the option of removing them if you wish. To do so simply follow these instructions:
</p>
<ol>
	<li>Create a new file, <span class="path">/path/to/your/controllers/CoreController.php</span></li>
	<li>
		In this new file, add the following code:
		<pre class="code-block">
&lt;?php
class CoreController extends Controller {
}
?&gt;</pre>
	</li>
	<li>Save the file.</li>
	<li>Now refresh this page and you should no longer be able to access the core interfaces!</li>
</ol>