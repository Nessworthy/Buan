<?php
/*
* Test relationship functions.
*
* @package UnitTest
*/
use Buan\Model;
use Buan\ModelRelation;

// 1:1
$test = $this->startTest('1:1 (1:M,1)');

$p = Model::create('Person');
$a1 = Model::create('Account');
$a2 = Model::create('Account');
$p->addRelatives($a1);
$test->addResult($p->findRelatives('Account')->contains($a1));

$p->addRelatives($a2);
$test->addResult(!$p->findRelatives('Account')->contains($a1));
$test->addResult($p->findRelatives('Account')->contains($a2));
$test->addResult($a1->findRelatives('Person')->isEmpty());

$p->disownRelatives($a2);
$test->addResult($p->findRelatives('Account')->isEmpty());
$test->addResult($a2->findRelatives('Person')->isEmpty());

$test->end();

// 1:M with limit
$test = $this->startTest('1:M with limit');

$p = Model::create('Person');
$a1 = Model::create('PersonAlias');
$a1->person_alias = "Alias 1";
$a2 = Model::create('PersonAlias');
$a2->person_alias = "Alias 2";
$a3 = Model::create('PersonAlias');
$a3->person_alias = "Alias 3";
$a4 = Model::create('PersonAlias');
$a4->person_alias = "Alias 4";
$p->addRelatives($a1);
$p->addRelatives($a2);
$p->addRelatives($a3);
$p->addRelatives($a4);
$rModels = $p->findRelatives('PersonAlias');
$test->addResult(count($rModels)==2);
$test->addResult($rModels[0]->person_alias==='Alias 3' && $rModels[1]->person_alias==='Alias 4');
$test->addResult($a1->findRelatives('Person')->isEmpty() && $a2->findRelatives('Person')->isEmpty());

$test->end();

// 1:M no limits
$test = $this->startTest('1:M no limits');

$lib = Model::create('Library');
$lib->name = 'My Library';
$b1 = Model::create('Book');
$lib->addRelatives($b1);
$test->addResult($lib->findRelatives('Book')->contains($b1));

$b2 = Model::create('Book');
$b2->addRelatives($lib);
$test->addResult($lib->findRelatives('Book')->contains($b1));
$test->addResult($lib->findRelatives('Book')->contains($b2));
$test->addResult($b2->findRelatives('Library')->contains($lib));

$test->end();

// Recursive 1:M
$test = $this->startTest('1:M recursive');

$parent = Model::create('Category');
$parent->title = 'Parent Category';
$c1 = Model::create('Category');
$c1->title = 'Child 1';
$parent->addRelatives($c1);
$test->addResult($parent->findRelatives('Category', ModelRelation::REF_PARENT)->contains($c1));
$test->addResult($c1->findRelatives('Category', ModelRelation::REF_CHILD)->contains($parent));

$c2 = Model::create('Category');
$c2->title = 'Child 2';
$c2->addRelatives($parent, ModelRelation::REF_CHILD);
$test->addResult($parent->findRelatives('Category', ModelRelation::REF_PARENT)->contains($c2));
$test->addResult($c2->findRelatives('Category', ModelRelation::REF_CHILD)->contains($parent));

$c1sub = Model::create('Category');
$c1sub->title = "Child 1 Sub";
$c1->addRelatives($c1sub);
$test->addResult($c1->findRelatives('Category', ModelRelation::REF_PARENT)->contains($c1sub));
$test->addResult($c1sub->findRelatives('Category', ModelRelation::REF_CHILD)->contains($c1));

$c2sub = Model::create('Category');
$c2sub->title = "Child 2 Sub";
$c2->addRelatives($c2sub);
$test->addResult($c2->findRelatives('Category', ModelRelation::REF_PARENT)->contains($c2sub));
$test->addResult($c2sub->findRelatives('Category', ModelRelation::REF_CHILD)->contains($c2));

$c1->disownRelatives($c1sub);
$test->addResult(!$c1->findRelatives('Category', ModelRelation::REF_PARENT)->contains($c1sub));
$test->addResult($c1sub->findRelatives('Category', ModelRelation::REF_CHILD)->isEmpty());

$parent->disownRelatives($c2);
$test->addResult(!$parent->findRelatives('Category', ModelRelation::REF_PARENT)->contains($c2));
$test->addResult($c2->findRelatives('Category', ModelRelation::REF_CHILD)->isEmpty());
$test->addResult($c2->findRelatives('Category', ModelRelation::REF_PARENT)->contains($c2sub));

$test->end();

/* -------------------------------------------------- M:M stuff is incomplete */
// M:M
$test = $this->startTest('M:M');

// Add non-recursive NP model to NP model via M:M relationship
$person1 = Model::create('Person');
$person1->name = "Joe";
$book1 = Model::create('Book');
$book1->title = 'Book ABC';
$person1->addRelatives($book1);
$test->addResult($person1->findRelatives('Book')->contains($book1));
$test->addResult($book1->findRelatives('Person')->contains($person1));
//$test->addResult($person1->getLinkingModel($book1)->modelName=='PersonBook');

$book2 = Model::create('Book');
$book2->title = 'Book DEF';
$book2->addRelatives($person1);
$test->addResult($person1->findRelatives('Book')->contains($book2));
$test->addResult($book2->findRelatives('Person')->contains($person1));
//$test->addResult($book2->getLinkingModel($person1)->modelName=='PersonBook');

$person1->disownRelatives($book1);
$test->addResult(!$person1->findRelatives('Book')->contains($book1));
$test->addResult(!$book1->findRelatives('Person')->contains($person1));
$test->addResult($book1->findRelatives('Person')->isEmpty());
//$test->addResult($person1->getLinkingModel($book1)===NULL);
//$test->addResult($book1->getLinkingModel($person1)===NULL);

$book2->disownRelatives($person1);
$test->addResult(!$person1->findRelatives('Book')->contains($book2));
$test->addResult(!$book2->findRelatives('Person')->contains($person1));
$test->addResult($book2->findRelatives('Person')->isEmpty());
$test->addResult($person1->findRelatives('Book')->isEmpty());
//$test->addResult($person1->getLinkingModel($book2)===NULL);
//$test->addResult($book2->getLinkingModel($person1)===NULL);

$test->end();

// M:M recursive
$test = $this->startTest('M:M recursive');

$bob = Model::create('Person');
$bob->name = 'Bob';
$joe = Model::create('Person');
$joe->name = 'Joe';
$bob->addRelatives($joe);
//$friend = $bob->getLinkingModel($joe);
$test->addResult($bob->findRelatives('Person', ModelRelation::REF_PARENT)->contains($joe));
$test->addResult($joe->findRelatives('Person', ModelRelation::REF_CHILD)->contains($bob));
//$test->addResult($friend->modelName=='Friend');

$test->end();return;

$joe->removeRelatedModel($bob);
$test->addResult(count($bob->getRelatedModels('Person', ModelRelation::REF_PARENT))===0);
$test->addResult(count($joe->getRelatedModels('Person', ModelRelation::REF_CHILD))===0);

$test->end();

// M:M with composite keys
// NOT YET SUPPORTED!
$test = $this->startTest('M:M with composite keys');

$p = Model::create('Person');
$p->name = 'Bob';
$a1 = Model::create('Address');
$a1->postcode = 'NE1';
$a1->houseno = '1';
$a2 = Model::create('Address');
$a2->postcode = 'NE2';
$a2->houseno = 2;
$p->addRelatives($a1);
$a2->addRelatives($p);
$test->addResult(in_array($a1, $p->findRelatives('Address'), TRUE) && in_array($a2, $p->findRelatives('Address'), TRUE) );
$test->addResult(in_array($p, $a1->findRelatives('Person'), TRUE) && in_array($p, $a2->findRelatives('Person'), TRUE));

// THE ABOVE RESULTS WILL RETURN TRUE, BUT THE LINKING MODEL USES FOREIGN KEYS:
//	address_id and person_id
// person_id is correct, but Addresses have a composite key consisting of: postcode,houseno
// So the linking model should actually be using:
//	postcode, houseno and person_id

$test->end();
?>