<?php
/*
* Test relationship functionality
*
* @package UnitTest
*/
use Buan\Database;
use Buan\Model;
use Buan\ModelManager;
use Buan\ModelCollection;
use Buan\ModelRelation;

echo "... skipping these test for now until the rewrite takes hold ...\n\n";
return;

/* ----------------------------------------------------------------- 1:M, M:1 */
$test = $this->startTest('1:M, 1:M,1 and M:1, non-composite keys, non-recursive');

// 1:M - Add relation to 1:M,1, initiated from 1-side
$l1 = Model::create('Library');
$b1 = Model::create('Book');
$l1->addRelatives($b1);
$test->addResult($l1->findRelatives('Book')->contains($b1));
$test->addResult($b1->findRelatives('Library')->contains($l1));

// 1:M - Initiate disown from 1-side
$l1->disownRelatives($b1);
$test->addResult($l1->findRelatives('Book')->isEmpty());
$test->addResult($b1->findRelatives('Library')->isEmpty());

// 1:M - Add relation, initiated from M-side
$l2 = Model::create('Library');
$b2 = Model::create('Book');
$b2->addRelatives($l2);
$test->addResult($l2->findRelatives('Book')->contains($b2));
$test->addResult($b2->findRelatives('Library')->contains($l2));

// 1:M - Initiate disown from M-side
$b2->disownRelatives($l2);
$test->addResult($l2->findRelatives('Book')->isEmpty());
$test->addResult($b2->findRelatives('Library')->isEmpty());

// 1:M - Add additional models to the M-side
$l1->addRelatives($b1);
$l1->addRelatives($b2);
$test->addResult($l1->findRelatives('Book')->contains($b1));
$test->addResult($l1->findRelatives('Book')->contains($b2));
$test->addResult($b1->findRelatives('Library')->contains($l1));
$test->addResult($b2->findRelatives('Library')->contains($l1));


// 1:M,1 - Add relation to 1:M,1, initiated from 1-side
$p1 = Model::create('Person');
$a1 = Model::create('Account');
$p1->addRelatives($a1);
$test->addResult($p1->findRelatives('Account')->contains($a1));
$test->addResult($a1->findRelatives('Person')->contains($p1));

// 1:M,1 - Initiate disown from 1-side
$p1->disownRelatives($a1);
$test->addResult($p1->findRelatives('Account')->isEmpty());
$test->addResult($a1->findRelatives('Person')->isEmpty());

// 1:M,1 - Add relation, initiated from M-side
$p2 = Model::create('Person');
$a2 = Model::create('Account');
$a2->addRelatives($p2);
$test->addResult($p2->findRelatives('Account')->contains($a2));
$test->addResult($a2->findRelatives('Person')->contains($p2));

// 1:M,1 - Initiate disown from M-side
$a2->disownRelatives($p2);
$test->addResult($p2->findRelatives('Account')->isEmpty());
$test->addResult($a2->findRelatives('Person')->isEmpty());

// 1:M,1 - Replace the M,1 model. This should remove the previous model
$p1->addRelatives($a1);
$p1->addRelatives($a2);
$test->addResult(!$p1->findRelatives('Account')->contains($a1));
$test->addResult(!$a1->findRelatives('Person')->contains($p1));
$test->addResult($p1->findRelatives('Account')->contains($a2));
$test->addResult($a2->findRelatives('Person')->contains($p1));

$test->end();


/* ---------------------------------------------------------------------- M:M */
$test = $this->startTest('M:M, non-composite keys, non-recursive');

// Add relative from both sides
$p1 = PersonModel::create();
$b1 = Model::create('Book');
$p1->addRelatives($b1);
$test->addResult($p1->findRelatives('Book')->contains($b1));
$test->addResult($b1->findRelatives('Person')->contains($p1));

$p2 = PersonModel::create();
$b2 = Model::create('Book');
$b2->addRelatives($p2);
$test->addResult($p2->findRelatives('Book')->contains($b2));
$test->addResult($b2->findRelatives('Person')->contains($p2));

// Disown relative from both sides
$b2->disownRelatives($p2);
$test->addResult($p2->findRelatives('Book')->isEmpty());
$test->addResult($b2->findRelatives('Person')->isEmpty());

$p1->disownRelatives($b1);
$test->addResult($p1->findRelatives('Book')->isEmpty());
$test->addResult($b1->findRelatives('Person')->isEmpty());

// Add multiple relatives via a collection
$c = new ModelCollection(array($b1, $b2));
$p1->addRelatives($c);
$test->addResult($p1->findRelatives('Book')->contains($b1));
$test->addResult($p1->findRelatives('Book')->contains($b2));

// Disown relative one at a time
$p1->disownRelatives($b1);
$test->addResult(!$p1->findRelatives('Book')->contains($b1));
$test->addResult($p1->findRelatives('Book')->contains($b2));
$p1->disownRelatives($b2);
$test->addResult(!$p1->findRelatives('Book')->contains($b1));
$test->addResult(!$p1->findRelatives('Book')->contains($b2));

// Implicit disowning when relative instance is changed. We need to do this via
// the linking model because by it's nature a M:M relationship can have many
// different relatives of the same type.
$p2 = Model::create('Person');
$p1->addRelatives($b1);
$test->addResult($p1->findRelatives('Book')->contains($b1));

$link = $p1->findLinkingRelative($b1);
$test->addResult($link->findRelatives('Book')->contains($b1));

$link->addRelatives($b2);		/* This should break the relationship between $link and $b1 */
$test->addResult(!$link->findRelatives('Book')->contains($b1));
$test->addResult($link->findRelatives('Book')->contains($b2));
$test->addResult($link->findRelatives('Person')->contains($p1));

$link->addRelatives($p2);		/* This should break the relationship between $link and $p1 */
$test->addResult(!$link->findRelatives('Book')->contains($p1));
$test->addResult($link->findRelatives('Person')->contains($p2));

$test->end();


/* ------------------------------------------------------------ 1:M recursive */
$test = $this->startTest('1:M, 1:M,1 and M:1 recursive, non-composite');

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


/* ------------------------------------------------------------ M:M recursive */
$test = $this->startTest('M:M recursive');

$bob = Model::create('Person');
$bob->name = 'Bob';
$joe = Model::create('Person');
$joe->name = 'Joe';
$bob->addRelatives($joe);
$friend = $bob->findLinkingRelative($joe);
$test->addResult($bob->findRelatives('Person', ModelRelation::REF_PARENT)->contains($joe));
$test->addResult($joe->findRelatives('Person', ModelRelation::REF_CHILD)->contains($bob));
$test->addResult($friend->modelName=='Friend');

$joe->disownRelatives($bob);
$test->addResult(count($bob->findRelatives('Person', ModelRelation::REF_PARENT))===0);
$test->addResult(count($joe->findRelatives('Person', ModelRelation::REF_CHILD))===0);

$test->end();
?>