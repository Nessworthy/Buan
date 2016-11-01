<?php
/*
* Test relationship functionality
*
* @package UnitTest
*/
use Buan\Model;
use Buan\ModelCriteria;
use Buan\ModelCollection;
use Buan\ModelManager;
use Buan\ModelRelation;

$mmBook = ModelManager::create('Book');
$mmLibrary = ModelManager::create('Library');
$mmPerson = ModelManager::create('Person');

/* ------------------------------------------------------- Loading and saving */
$test = $this->startTest('Basic loading and saving');

// Load some persistent models
$l1 = Model::create('Library');
$l1->id = 1;
$test->addResult($mmLibrary->load($l1)===TRUE);

$l2 = Model::create('Library');
$l2->id = 1;
$test->addResult($mmLibrary->load($l2)===TRUE);

// Ensure instance uniqueness
$test->addResult($l1!==$l2);

// Check flags
$test->addResult($l1->isInDatabase());
$test->addResult(!$l1->hasChanged());

// Save (no changes)
$test->addResult($mmLibrary->save($l1)===TRUE);

// Save changes and reload new instance from db
$l1->name = 'new name';
$test->addResult($mmLibrary->save($l1)===TRUE);
$test->addResult($l1->name==='new name');

$l3 = Model::create('Library');
$l3->id = 1;
$test->addResult($mmLibrary->load($l3)===TRUE);
$test->addResult($l3->name==='new name');
unset($l3);

// Add NP 1:M relation and save
$b1 = Model::create('Book');
$b1->title = 'THIS IS B1';
$l1->addRelatives($b1);
$test->addResult($mmBook->save($b1)===TRUE);
$test->addResult($b1->library_id===$l1->id);
$test->addResult($b1->getPrimaryKeyValue()>0);
unset($b1);

// Add NP M:1 relation and save
$l4 = Model::create('Library');
$l4->name = 'Library #4';
$b1 = Model::create('Book');
$b1->title = 'NP Book';
$b1->addRelatives($l4);
$test->addResult($mmBook->save($b1)===TRUE);

$test->end();
unset($p1, $l1, $l2, $l3, $l4, $b1);

/* ---------------------------------------------------------------------- 1:1 */
$test = $this->startTest('1:1 (1:M,1)');

// Load some persistent models
$p1 = Model::create('Person');
$p1->id = 1;
$mmPerson->load($p1);

// 1:M - Add non-persistent relation to 1:M,1, initiated from 1-side
$a1 = Model::create('Account');
$p1->addRelatives($a1);
$test->addResult($p1->findRelatives('Account')->contains($a1));
$test->addResult($p1->findRelatives('Account')->get(0)===$a1);
$test->addResult($a1->findRelatives('Person')->contains($p1));
$test->addResult($a1->findRelatives('Person')->get(0)===$p1);
$test->addResult(count($p1->findRelatives('Account'))===1);

// 1:M - Add relation to 1:M,1, initiated from M-side
$a2 = Model::create('Account');
$a2->addRelatives($p1);
$test->addResult(!$p1->findRelatives('Account')->contains($a1));
$test->addResult($p1->findRelatives('Account')->contains($a2));
$test->addResult($a1->findRelatives('Person')->isEmpty());

// 1:M - Initiate disown from 1-side
$p1->disownRelatives($a2);
$test->addResult(!$p1->findRelatives('Account')->contains($a2));
$test->addResult($p1->findRelatives('Account')->isEmpty());

$test->end();
unset($p1, $a1, $a2);

/* ----------------------------------------------------------------- 1:M, M:1 */
$test = $this->startTest('1:M, M:1');

// Reload models
$l1 = Model::create('Library');
$l1->id = 1;
$mmLibrary->load($l1);

$b1 = Model::create('Book');
$b1->id = 1;
$test->addResult($mmBook->load($b1)===TRUE);

// 1:M - Add P model to P model, from 1-side
// The book model already exists in the library, but we're adding it again so
// we need to make sure it only occurs once in the collection, from both the
// 1 and M point of view
$l1->addRelatives($b1);
$count = 0;
foreach($l1->findRelatives('Book') as $book) {
	$count += $book->id==$b1->id ? 1 : 0;
}
$test->addResult($count==1);
$test->addResult($l1->findRelatives('Book')->contains($b1));
$count = 0;
foreach($b1->findRelatives('Library') as $library) {
	$count += $library->id==$l1->id ? 1 : 0;
}
$test->addResult($count==1);
$test->addResult($b1->findRelatives('Library')->contains($l1));
unset($count, $book, $library);

// 1:M - Add NP model to P model, from 1-side
$b1 = Model::create('Book');
$l1->addRelatives($b1);
$test->addResult($l1->findRelatives('Book')->contains($b1));
$test->addResult($b1->findRelatives('Library')->contains($l1));

// 1:M - Add duplicate NP model to P model, from 1-side
$pre = $l1->findRelatives('Book');
$l1->addRelatives($b1);
$post = $l1->findRelatives('Book');
$test->addResult($post->contains($b1));
$test->addResult(count($post)===count($pre));

// 1:M - Add NP model to P model, from M-side
$b2 = Model::create('Book');
$b2->addRelatives($l1);
$test->addResult($l1->findRelatives('Book')->contains($b2));
$test->addResult($b2->findRelatives('Library')->contains($l1));

// 1:M - Add duplicate NP model to P model, from M-side
$pre = $l1->findRelatives('Book');
$b2->addRelatives($l1);
$post = $l1->findRelatives('Book');
$test->addResult($post->contains($b2));
$test->addResult(count($post)===count($pre));

// 1:M - Initiate disown from M-side
$b1->disownRelatives($l1);
$test->addResult(!$l1->findRelatives('Book')->contains($b1));
$test->addResult(!$b1->findRelatives('Library')->contains($l1));

// 1:M - Initiate disown from 1-side
$l1->disownRelatives($b2);
$test->addResult(!$l1->findRelatives('Book')->contains($b2));
$test->addResult(!$b2->findRelatives('Library')->contains($l1));

$test->end();

/* ---------------------------------------------------------------------- M:M */
$test = $this->startTest('M:M, non-composite keys, non-recursive');

// Load M:M relative from db
$b1 = Model::create('Book');
$b1->id = 1;
$b1->getModelManager()->load($b1);
$test->addResult(!$b1->findRelatives('Person')->isEmpty());

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

//$test->end();return;

// Implicit disowning when relative instance is changed. We need to do this via
// the linking model because by it's nature a M:M relationship can have many
// different relatives of the same type.
/*
$p2 = Model::create('Person');
$p1->addRelatives($b1);
$test->addResult($p1->findRelatives('Book')->contains($b1));

$link = $p1->findLinkingRelative($b1);
$test->addResult($link->findRelatives('Book')->contains($b1));

$link->addRelatives($b2);		// This should break the relationship between $link and $b1
$test->addResult(!$link->findRelatives('Book')->contains($b1));
$test->addResult($link->findRelatives('Book')->contains($b2));
$test->addResult($link->findRelatives('Person')->contains($p1));

$link->addRelatives($p2);		// This should break the relationship between $link and $p1
$test->addResult(!$link->findRelatives('Book')->contains($p1));
$test->addResult($link->findRelatives('Person')->contains($p2));
*/

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
/*
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
*/
?>