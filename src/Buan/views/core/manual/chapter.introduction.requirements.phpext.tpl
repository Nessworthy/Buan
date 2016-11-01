<?php
/* $Id$ */
?>

<p>
	The following PHP extensions are required for the core of Buan to operate:
</p>
<ul>
	<li><a href="http://www.php.net/sqlite">sqlite</a></li>
	<li><a href="http://www.php.net/pdo">pdo</a> (also required by sqlite)</li>
</ul>

<p>
	If you wish to use Buan's built-in ORM (Object Relational Mapping) system for handling database transactions then you may also need to enable the database-specific PDO driver for your database of choice (eg. if you use MySQL then you may have to enable <span class="code">PDO_MYSQL</span>.) See the <a href="http://www.php.net/pdo">PHP documentation</a> for more details.
</p> 