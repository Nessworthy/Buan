<?php
/* $Id$ */
?>

<p>
	In this example we'll create the Model structures necessary to build a new blogging system from the ground up.
</p>


<h3>Step 1 - table creation</h3>
<p>
	Our blog will use the following basic tables:
</p>
<ul>
	<li><span class="code">blog</span> - holds all the blogs that are managed on your system</li>
	<li><span class="code">blog_entry</span> - entries belonging to a blog</li>
	<li><span class="code">comment</span> - user comments attached to blog entries</li>
</ul>
<p>
	By default Buan assumes that each table has a primary key called <span class="code">id</span> which is a simple auto-incrementing integer. However, you can use any primary-key you wish by defining it in the <span class="code">$dbTablePrimaryKey</span> class property.
</p>
<p>
	Let's have a look at the SQL behind creating the tables in our example:
</p>

<pre class="code-block">
<b>/* 'blog' */</b>
CREATE TABLE blog (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(50)
)
</pre>

<pre class="code-block">
<b>/* 'blog_entry' */</b>
CREATE TABLE blog_entry (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	blog_id INT UNSIGNED,
	title VARCHAR(100),
	body TEXT,
	datecreated DATETIME
)
</pre>

<pre class="code-block">
<b>/* 'comment' */</b>
CREATE TABLE entry (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	blog_entry_id INT UNSIGNED,
	comment TEXT,
	datecreated DATETIME
)
</pre>


<h3>Step 2 - create the Model classes</h3>
<p>
	Each of the following files must now be created and saved in the folder defined in <span class="path">[app.dir.models]</span>:
</p>
<pre class="code-block">
&lt;?php
<span class="comment">/* BlogModel.php */</span>

class BlogModel extends Model {
	protected $dbTableName = 'blog';
}
?&gt;
</pre>

<pre class="code-block">
&lt;?php
<span class="comment">/* BlogEntryModel.php */</span>

class BlogEntryModel extends Model {
	protected $dbTableName = 'blog_entry';
}
?&gt;
</pre>
<pre class="code-block">
&lt;?php
<span class="comment">/* CommentModel.php */</span>

class CommentModel extends Model {
	protected $dbTableName = 'comment';
}
?&gt;
</pre>
<p>
	Note that if you plan to follow Buan's recommended naming conventions for database tables, field names and class names, then you can ignore the table-definition line as Buan will work this out based on your Models' class names. For example, our <span class="code">Comment</span> Model's class could be defined simply as:
</p>
<pre class="code-block">
&lt;?php
<span class="comment">/* Comment.php */</span>

class Comment extends Model {
}
?&gt;
</pre>

<h3>Step 3 - define Model relationships</h3>
<p>
	This is an important step in harnessing the full power of Buan's relational-modelling system. By using the <span class="code">ModelRelation</span> class, we can build a detailed overview of how Models are related to each other.
</p>
<p>
	The following definition can be added to the <span class="path">[app.configPath]/app.php</span> script:
</p>
<pre class="code-block">
&lt;?php
<span class="comment">/* [app.dir.config]/app.php */</span>
...
ModelRelation::define('Blog(1):BlogEntry(M)');		<span class="comment">/* Define a 1:M relationship between Blog and BlogEntry */</span>
ModelRelation::define('BlogEntry(1):Comment(M)');	<span class="comment">/* Define a 1:M relationship between BlogEntry and Comment */</span>
...
?&gt;
</pre>


<h3>Step 4 - using the Models</h3>
<p>
	Here are some examples of common usage:
</p>
<pre class="code-block">&lt;?php
// Create a new blog
$blog = Model::create('Blog');
$blog->name = "My Blog!";

// Make a first entry
$entry = Model::create('BlogEntry');
$entry->title = "What did I do today?";
$entry->body = "Wrote a blog entry :)";
$entry->addRelatedModel($blog);

// Save
// This will actually save $blog too as we need $blog's primary key in order
// to use it in $entry's database record.
$entry->getModelManager->save($entry);

// Load all entries in another, previously saved blog
$blog = Model::create('Blog');
$blog->name = "SuperBlog";
if(!$blog->getModelManager()->loadByName($blog)) {
	// ...
}
$blog->loadRelatedModels('BlogEntry');
$entries = $blog->getRelatedModels('BlogEntry');
?&gt;
</pre>
<p>
	There are/will be shortcuts to some of these functions as the framework develops, but these are the very basic controls available.
</p>