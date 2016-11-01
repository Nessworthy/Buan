<?php
/* $Id: chapter.conventions.tpl 155 2007-09-17 10:48:30Z james $ */
?>

<p>
	Conventions are good. If you're working in a small team they're very useful. If you're a small cog in a gigantic mechanism of mashing wheels they're essential.
</p>

<h3>Naming Stuff</h3>
<p>
	Buan uses the following naming conventions throughout:
</p>
<ul>	
	<li>
		<span class="keyword">lowerCamelCaps</span>
		<p>
			<b>rule:</b> Each word, except the first, in a name must begin with a capital letter. The first word is all lowercase. All words are all joined together.<br/>
			<b>example:</b> "get product list" --> "getProductList"<br/>
			<b>general application:</b> Filenames, variables, class methods and properties, function names, Buan config namespaces and variables
		</p>
	</li>
	<li>
		<span class="keyword">UpperCamelCaps</span>
		<p>
			<b>rule:</b> Each word in a name must begin with a capital letter. All words are joined together.<br/>
			<b>example:</b> "product category" --> "ProductCategory"<br/>
			<b>general application:</b> Model names, class names, class filenames
		</p>
	</li>
	<li>
		<span class="keyword">lower-hyphenated</span>
		<p>
			<b>rule:</b> Each word in the name is lowercased. All words are joined together with a single hyphen character.<br/>
			<b>example:</b> "view invoices" --> "view-invoices"<br/>
			<b>general application:</b> Urls, CSS classes, XML node identifiers
		</p>
	</li>
	<li>
		<span class="keyword">UPPER-HYPHENATED</span>
		<p>
			<b>rule:</b> Each word is uppercased. All words are joined together with a single hyphen character.<br/>
			<b>example:</b> "read me" --> "READ-ME"<br/>
			<b>general application:</b> <i>None so far</i>
		</p>
	</li>
	<li>
		<span class="keyword">lower_underscored</span>
		<p>
			<b>rule:</b> Each word is lowercased. All words are joined together with a single underscore character.<br/>
			<b>example:</b> "order item" --> "order_item"<br/>
			<b>general application:</b> Database table and field names
		</p>
	</li>
	<li>
		<span class="keyword">UPPER_UNDERSCORED</span>
		<p>
			<b>rule:</b> Each word is uppercased. All words are joined together with a single underscore character.<br/>
			<b>example:</b> "keyword type" --> "KEYWORD_TYPE"<br/>
			<b>general application:</b> Class and global constants
		</p>
	</li>
</ul>