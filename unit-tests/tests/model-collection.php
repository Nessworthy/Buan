<?php
/*
* Test collection object.
*
* @package UnitTest
*/
use Buan\Database;
use Buan\Model;
use Buan\ModelCollection;
use Buan\ModelCriteria;
use Buan\ModelManager;

$mmLib = ModelManager::create('Library');

// Models, arrays
$test = $this->startTest('Create collection from simple Models, arrays');

$lib1 = Model::create('Library');
$c1 = new ModelCollection($lib1);
$test->addResult($c1->contains($lib1));

$lib2 = Model::create('Library');
$c2 = new ModelCollection(array($lib1, $lib2));
$test->addResult($c2->contains($lib1));
$test->addResult($c2->contains($lib2));

$test->end();

// Models, arrays
$test = $this->startTest('Create collection from PDO result');

$DB = Database::getConnectionByModel();
$c = new ModelCriteria();
$c->selectField("`book`.*");
$c->selectTable('book');
$sql = $c->sql();
$stmt = $DB->prepare($sql->query);
$stmt->execute();
$c1 = new ModelCollection('Book', $stmt);
$test->addResult($c1[6]->modelName=='Book');
$test->addResult(!$c1->isEmpty());

$test->end();

// Merge collections
$test = $this->startTest('Merge collections');
$test->end();
?>