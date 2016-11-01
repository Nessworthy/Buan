<?php
/**
* @package UnitTest
*/
use Buan\Autoloader;
use Buan\Config;
use Buan\Database;
use Buan\Model;
use Buan\ModelManager;

// Class paths
AutoLoader::addClassPath(dirname(dirname(__FILE__)).'/lib');

// Remove any previously created databases
$dbInfo = Database::getConnectionInfo('default');
$dbFile = preg_replace("/^sqlite:(.*?)$/i", "$1", $dbInfo['dsn']);
if(file_exists($dbFile)) {
	unlink($dbFile);
}

// Create test database
$schema = new SqlDumpIterator(file_get_contents(dirname(__FILE__).'/db-schema.sql'));
foreach($schema as $query) {
	try {
		$stmt = ModelManager::sqlQuery($query);
	}
	catch(Exception $e) {
		die($e->getMessage());
	}
}


// Start building some objects for testing persistent stuff
echo "Populating database ... ";
ob_flush();
ModelManager::sqlQuery('BEGIN TRANSACTION');
//ModelManager::sqlQuery('START TRANSACTION');	/* Version for MySql */

// Populate: Library with books
for($i=0; $i<2; $i++) {
	$sql = 'INSERT INTO library (id, name) VALUES ('.($i+1).', "LIB('.uniqid(rand()).')")';
	ModelManager::sqlQuery($sql);
}

for($i=0; $i<500; $i++) {
	$sql = 'INSERT INTO book (id, library_id, title) VALUES ('.($i+1).', 1, "BOOK('.uniqid(rand()).')")';
	ModelManager::sqlQuery($sql);
}

for($j=$i; $j<$i+100; $j++) {
	$sql = 'INSERT INTO book (id, library_id, title) VALUES ('.($j+1).', 2, "BOOK('.uniqid(rand()).')")';
	ModelManager::sqlQuery($sql);
}

// Populate: People and accounts
// Populate: Library with books
for($i=0; $i<5; $i++) {
	$sql = 'INSERT INTO person (id, name, age) VALUES ('.($i+1).', "NAME('.uniqid(rand()).')", '.rand(25,50).')';
	ModelManager::sqlQuery($sql);
}

// Populate: People have books
$sql = 'INSERT INTO person_book (id, person_id, book_id) VALUES (1, 1, 1)';
ModelManager::sqlQuery($sql);

// Commit changes
ModelManager::sqlQuery('COMMIT');
echo "done\n\n";
ob_flush();
?>