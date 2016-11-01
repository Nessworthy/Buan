<?php
/*
* General functionality related to Models.
*
* @package UnitTest
*/

use Buan\Model;

// General
$test = $this->startTest('General');

$m = Model::create('NonExistent');
$test->addResult(get_class($m)==='Buan\Model' && $m->modelName==='NonExistent');
$test->addResult($m->getDbTableName()==='non_existent');

$m = Model::create('Person');
$test->addResult(get_class($m)==='PersonModel' && $m->modelName==='Person');
$test->addResult($m->getDbTableName()==='person');
$test->addResult($m->getPrimaryKey()==='id');

$m = Model::create('Address');
$test->addResult($m->hasCompositePrimaryKey() && is_array($m->getPrimaryKeyValue()) && $m->getPrimaryKey()==='postcode,houseno');

$test->end();

// Get/set class properties
$test = $this->startTest('Get/set class properties (non-primary keys)');

$m = Model::create('Person');
$m->name = 'Bob';
$test->addResult($m->name==='Bob');
$test->addResult(in_array('name', array_keys($m->getDbData())));
$test->addResult(isset($m->name)===TRUE);
$test->addResult(isset($m->nonExistent)===FALSE);

$m->age = 18;
$test->addResult($m->age===18);

$m->wage = 25.50;
$test->addResult($m->wage===25.50);

$m->nullValue = NULL;
$test->addResult($m->nullValue===NULL);
$test->addResult(isset($m->nullValue)===FALSE);

$m->populateFromArray(array('f1'=>1, 'f2'=>2, 'f3'=>3));
$test->addResult(isset($m->f1, $m->f2, $m->f3));

$m->channels = array("HI");
$test->addResult($m->channels instanceof ArrayObject && in_array("HI", (array)$m->channels, TRUE));

$m->channels[] = "BYE";
$test->addResult(in_array("BYE", (array)$m->channels, TRUE));

$m->channels['KEY'] = "HELLO";
$test->addResult(array_key_exists("KEY", (array)$m->channels));

$m->styles = array();
$m->styles[] = new StdClass();
$m->styles[0]->font = 'Arial';
$test->addResult($m->styles[0] instanceof StdClass && $m->styles[0]->font==='Arial');

$m = Model::create('Person');
$m->favourite_colour = 'Yellow';
$test->addResult($m->favourite_colour==='XYZYellow');

$test->end();

// Set change and persistence flags
$test = $this->startTest('Setting change/persistence flags');

$m = Model::create('Person');
$test->addResult($m->hasChanged()===TRUE);
$test->addResult($m->isInDatabase()===FALSE);

$m->hasChanged(FALSE);
$test->addResult($m->hasChanged()===FALSE);

$m->hasChanged(TRUE);
$test->addResult($m->hasChanged()===TRUE);

$m->isInDatabase(FALSE);
$test->addResult($m->isInDatabase()===FALSE);

$m->isInDatabase(TRUE);
$test->addResult($m->isInDatabase()===TRUE);

$test->end();

// Get/set primary keys
$test = $this->startTest('Get/set simple primary keys');

$m = Model::create('Person');
$test->addResult($m->getPrimaryKey()==='id');
$test->addResult($m->getPrimaryKeyValue()===NULL);

$m->id = 70;
$test->addResult($m->id===70);
$test->addResult($m->getPrimaryKeyValue()===70);

$m->setPrimaryKeyValue(100);
$test->addResult($m->id===100);
$test->addResult($m->getPrimaryKeyValue()===100);

$m->setPrimaryKeyValue(NULL);
$test->addResult($m->id===NULL);
$test->addResult($m->getPrimaryKeyValue()===NULL);

$test->end();

// Get/set composite primary keys
$test = $this->startTest('Get/set composite primary keys');

$m = Model::create('Address');
$test->addResult($m->getPrimaryKey()==='postcode,houseno');
$test->addResult(is_array($m->getPrimaryKeyValue()) && in_array('postcode', array_keys($m->getPrimaryKeyValue())) && in_array('houseno', array_keys($m->getPrimaryKeyValue())));

$m->postcode = 'NE26';
$m->houseno = 33;
$test->addResult(is_array($m->getPrimaryKeyValue()) && $m->postcode==='NE26' && $m->houseno===33);

$m->setPrimaryKeyValue(array('postcode'=>'NE26', 'houseno'=>31));
$test->addResult($m->postcode==='NE26' && $m->houseno===31);

$m->setPrimaryKeyValue('postcode', 'NE20');
$test->addResult($m->postcode==='NE20' && $m->houseno===31);

$test->end();
?>