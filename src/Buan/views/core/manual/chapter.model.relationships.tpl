<?php
/* $Id$ */
?>

<p>
	Relationships between your Models are defined using the <span class="code">ModelRelation::define()</span> method. For example:
</p>
<pre class="code-block">ModelRelation::define('Person(1):Pet(M)');
ModelRelation::define('Book(M):Author(M)');
ModelRelation::define('Category(M):CategoryLinks.left_id.right_id(1):Category(M)');
</pre>
<p>
	See the API documentation for more details on the complexities of this powerful function.
</p>

<h3>1:M</h3>
<p>
	Definition examples:
</p>
<pre class="code-block">ModelRelation::define('House(1):Window(M)');			<span class="comment">/* Presumes "window.house_id" FK */</span>
ModelRelation::define('Human(1):Limb.person_id(M)');		<span class="comment">/* Specifies "limb.person_id" FK */</span>
ModelRelation::define('Category(1):Product(M)', 'nocascade');	<span class="comment">/* Prevents cascading delete */</span>
ModelRelation::define('Category(1):Category(M)');	<span class="comment">/* A recursive relationship. See related section below. */</span>
</pre>
<p>
	Read/write examples:
</p>
<pre class="code-block">$house = Model::create('House');
$window = Model::create('Window');
$house->addRelatedModel($window);

<span class="comment">/* For a recursive relationship */</span>
$catA = Model::create('Category');
$catB = Model::create('Category');
$catA->addRelatedModel($catB);	<span class="comment">/* $catB becomes a child of $catA */</span>
$catB->addRelatedModel($catA, ModelRelation::REF_CHILD);	<span class="comment">/* Same as above, but indicating "Set $catA as $catB's parent" */</span>
</pre>

<h3>M:1</h3>
<p>
	Definition examples:
</p>
<pre class="code-block">ModelRelation::define('Leaf(M):Tree(1)');
</pre>
<p>
	Read/write examples:
</p>
<pre class="code-block">$leaf = Model::create('Leaf');
$tree = Model::create('Tree');
$leaf->addRelatedModel($tree);
</pre>

<h3>M:M</h3>
<p>
	Many-to-many relationships are actually defined internally within Buan as 1:M:1 relationships. The "M" in this relationship is the <span class="keyword">linking Model</span> that joins the two "1" Models together.
</p>
<p>
	Furthermore, this relationship is further broken down into two sub-relationships; a 1:M and a M:1.
</p>
<p>
	Definition examples:
</p>
<pre class="code-block">ModelRelation::define('Book(M):Author(M)');			<span class="comment">/* Presumes linking model "BookAuthor" with FKs "book_id" and "author_id" */</span>
ModelRelation::define('Friend(1):Mates.left_id.right_id(M):Friend(1)');		<span class="comment">/* Recursive relationship, "Mates" is linking Model */</span>
</pre>
<p>
	Read/write examples:
</p>
<pre class="code-block">
</pre>

<h3>Recursive relationships</h3>
<p>
	As shown above, Buan handles recursive relationships by means of <span class="keyword">relationship references</span>.
</p>
<p>
	In a typical 1:M relationship you have a parent (the "1") and a child (the "M").
</p>