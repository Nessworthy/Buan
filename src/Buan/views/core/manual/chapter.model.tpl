<?php
/* $Id: chapter.model.tpl 155 2007-09-17 10:48:30Z james $ */
?>

<p>
	In Buan, a <em>Model</em> is an object that corresponds to a single record in a database table. For every table you create, you should also create a corresponding Model class.
</p>

<h3>Class location</h3>
<p>
	You must create a Model class within either the <span class="path">[app.dir.models]</span>, <span class="path">[core.dir.models]</span> or <span class="path">[ext.*.dir.models]</span> folder, name it using the <span class="keyword">UpperCamelCaps</span> convention and always suffix with the word <b>Model</b>. For example:
</p>
<pre class="code-block">[app.dir.models]/BookModel.php
[app.dir.models]/ProductCategoryModel.php
</pre>

<h3>Class structure</h3>
<p>
	The basic structure of a Model class is as follows:
</p>
<pre class="code-block">&lt;?php
/* Placed in, for example, [app.dir.models]/BlogModel.php */

class BlogModel extends Model {

	protected $dbConnectionName = 'default';	<span class="comment">/* optional, defaults to 'default' */</span>

	protected $dbTableName = 'blog';		<span class="comment">/* optional, defaults to lower_underscored version of class name minus 'Model' suffix */</span>

	protected $dbTablePrimaryKey = 'id';		<span class="comment">/* optional, defaults to 'id' */</span>
}
?&gt;
</pre>
<p>
	Here we've defined a Model that will handle entries in the <span class="path">blog</span> database table, which are indexed by the primary key column <span class="path">id</span>.
</p>
<p>
	The <span class="path">blog</span> table can be found in the database that is defined in the <span class="path">default</span> connection.
</p>





<h2>Naming Conventions</h2>
<p>
	When it comes to naming your Models and databases, the only restriction that you <em>must</em> adhere to is that your Model names and classes must use the <span class="keyword">UpperCamelCaps</span> naming convention.
</p>
<p>
	For example, for a Model named <span class="code">BlogEntry</span>:
</p>
<pre class="code-block"># Model name
BlogEntry

# Model class filename and definition
BlogEntryModel.php
class BlogEntryModel extends Model {
	...
}

# ModelManager class filename and definition
BlogEntryManager,php
class BlogEntryManager extends ModelManager {
	...
}
</pre>

<h2>2.2.2 The ModelManager</h2>
<p>
	Every Model has an associated <span class="keyword">ModelManager</span>, the primary role of which is to handle all <span class="keyword">CRUD</span> (<span class="keyword">C</span>reate, <span class="keyword">R</span>eplace, <span class="keyword">U</span>pdate</span> and <span class="keyword">D</span>elete) operations associated with that Model. This allows you to keep the Model classes themselves very lightweight, because all the heavy database operations are handled by singleton instances of the ModelManagers.
</p>
<p>
	A single ModelManager instance handles CRUD operations for every instance of it's associated Model.
</p>
<p>
	For example:
</p>
<pre class="code-block">// Create the Model instance
$m = Model::create('Invoice');

// Load a row from the database using the primary key, 'id'
$m->id = 75;
$m->getModelManager()->load($m);

// Make some changes and save back to the database
$m->amount = 56.00;
$m->getModelManager()->save($m);

// Delete the row form the database
$m->getModelManager()->delete($m);</pre>
<p>
	You're probably thinking this code looks very long-winded, and you'd be right. The reason all boils down to how Buan handles in-memory Model instances, their relationships (1:M, M:M, etc) and the way it propagates instance replacements. This is all encompassed within the <span class="keyword">ModelTracker</span> class.
</p>

<h2>2.2.3 The ModelTracker</h3>
<p>
	You can think of the ModelTracker as an in-memory repository of previously loaded Model instances, which is globally accessible. The ideas behind it:
</p>
<ul>
	<li>You can load Model A from multiple loctions within your application, knowing that each instance is actually referencing the same instance in the ModelTracker. The primary goal here is to keep a Model's list of loaded relations "fresh" across the whole application.</li>
	<li>Previous point has side effect of reducing memory usage in cases of Models being loaded multiple times.</li>
</ul>
<p>
	[TODO: Option to turn this off?]
</p>