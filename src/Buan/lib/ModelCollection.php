<?php
/**
* This is a *special* container for handling a collection of Models and offers
* a few benefits over your bog standard arrays. It acts just like an array in
* every other respect, apart a few gotchas:
*
* 1. You can't pass an instance of a ModelCollection to method who expect an
*		array (eg. implode()). Instead you must cast it to an array beforehand using
*		the ->asArray() method.
*
* If you store a PDO Statement in a ModelCollection, then you'll need to enable
* the unbuffering of result sets.
*
* @todo Add magic method for passing unknown method calls through to ALL models
* in the collection. Ala jquery.
*
* @package Buan
*/
namespace Buan;
use Buan\Model;
class ModelCollection implements \Iterator, \ArrayAccess, \Countable {

	/**
	* The iterable element currently in focus.
	*
	* @var array|\Buan\ModelCollection|StdClass
	*/
	protected $active;

	/**
	* Flag to tell us if the collection is exhausted or not.
	*
	* @var bool
	*/
	protected $exhausted;

	/**
	* Flag to tell the collection to contain unique persistent Models.
	* Collections are unique by default.
	*
	* @var bool
	*/
	protected $isUnique;

	/**
	* An array of Models collected from the collection's queues so far.
	*
	* @var array
	*/
	protected $models;

	/**
	* If the "unique" flag is set then any persistent models will be stored here,
	* indexed by it's persistent id. This is used to pervent duplicate results
	* from appearing in $this->models.
	*
	* @var array
	*/
	protected $persistentModels;

	/**
	* A queue of iterable elements that are accumulated via the ->append() method.
	* This queue is processed in LILO sequence.
	*
	* @var array
	*/
	protected $queue;

	/**
	* There are 3 ways to create a collection:
	*
	* 1. Pass an array of Models:
	*		new ModelCollection(array(...));
	*
	* 2. Pass a single Model:
	*		new ModelCollection($model);
	*
	* 3. Pass a PDO statement and the name of the Models it will return:
	*		new ModelCriteria($modelName, $stmt)
	*
	* @param string|array|Buan\ModelCollection ** see description above **
	* @param \Buan\PdoStatement $stmt A PDO result to traverse over
	* @return \Buan\ModelCollection
	*/
	public function __construct($modelsOrName=array(), $stmt=NULL) {
		$this->active = NULL;
		$this->exhausted = FALSE;
		$this->isUnique = TRUE;
		$this->models = array();
		$this->persistentModels = array();
		$this->queue = array();
		if($modelsOrName instanceof Model) {
			$this->queue[] = array($modelsOrName);
		}
		else if(is_string($modelsOrName) && $stmt!==NULL) {
			$obj = new \StdClass();
			$obj->modelName = $modelsOrName;
			$obj->stmt = $stmt;
			$this->queue[] = $obj;
		}
		else if(is_array($modelsOrName) && !empty($modelsOrName)) {
			$this->queue[] = $modelsOrName;
		}
	}

	/**
	* Cleans up the collection to free up memory and close DB connections.
	*
	* @return void
	*/
	public function __destruct() {
		foreach($this->queue as $q) {
			if($q instanceof \StdClass) {
				if($q->stmt!==NULL) {
					$q->stmt->closeCursor();
					unset($q->stmt);
				}
			}
		}
	}

	/**
	* Merge $this collection with the specified collection(s).
	*
	* @param \Buan\ModelCollection Collection to add
	* @return void
	*/
	public function append(ModelCollection $collection) {
		$this->queue[] = $collection;
	}

	/**
	* Returns the whole collection as an array.
	* Requires exhausting.
	*
	* @return array
	*/
	public function asArray() {
		$this->exhaust();
		return $this->models;
	}

	/**
	* Returns the whole collection as an array of raw db data, rather than models.
	*
	* @return array
	*/
	public function asDbDataArray() {
		$this->exhaust();
		$models = array();
		foreach($this->models as $k=>$m) {
			$models[$k] = $m->getDbData();
		}
		return $models;
	}

	/**
	* Determine if $this collection contains the specified Model, $model.
	* We have to cycle through the whole collection to determine this, so if you
	* expect the collection to be very large do something different!
	*
	* @param \Buan\Model $model
	* @return bool
	*/
	public function contains($model) {
		$result = FALSE;
		foreach($this as $m) {
			if($model===$m) {
				$result = TRUE;
				break;
			}
		}
		return $result;
	}

	/**
	* Return total number of Models in this collection.
	* Requires exhausting.
	*
	* @return int
	*/
	public function count() {
		$this->exhaust();
		return count($this->models);
	}

	/**
	* @return \Buan\Model
	*/
	public function current() {
		return current($this->models);
	}

	/**
	* Get all models from all queued element until the queue is exhausted.
	* This kind of thing is essential for using count(), offset*(), etc.
	*
	* @return void
	*/
	public function exhaust() {
		if(!$this->exhausted) {
			while($this->valid()) {
				$this->current();
				$this->next();
			}
			$this->rewind();
			$this->exhausted = TRUE;
		}
	}

	/**
	* Alias for ::offsetGet()
	*
	* @return mixed
	*/
	public function get($index) {
		return $this->offsetGet($index);
	}

	/**
	* Determines if this collection is empty.
	* Requires exhausting.
	*
	* @return bool
	*/
	public function isEmpty() {
		$this->exhaust();
		return empty($this->models);
	}

	/**
	* @return int
	*/
	public function key() {
		return key($this->models);
	}

	/**
	* Prepare the next item in our collection.
	*
	* @return void
	*/
	public function next() {

		// If, after moving to the next item in $models we end up with a blank, then
		// try looking at the next queued element
		next($this->models);
		if(!current($this->models)) {

			// See if a queue element is already active
			if($this->active!==NULL) {

				// It's a normal array so move the whole lot into $models. We can't use
				// array_merge here is the unique flag is set as we need to prevent
				// duplicates from being added to $this->models, so need to check the
				// persistent IDs.
				if(is_array($this->active)) {
					if($this->isUnique) {
						foreach($this->active as $m) {
							if($m->isInDatabase()) {
								$pId = $m->getPersistentId();
								if(!isset($this->persistentModels[$pId])) {
									$this->models[] = $this->persistentModels[$pId] = $m;
								}
							}
							else {
								$this->models[] = $m;
							}
						}
					}
					else {
						$this->models = array_merge($this->models, $this->active);
					}
					$this->active = NULL;
				}

				// It's a stream so try and load it's next result and add to $models, if
				// it's not already in there.
				else if($this->active instanceof \StdClass) {
					if($record = $this->active->stmt->fetch(\PDO::FETCH_ASSOC)) {
						$model = Model::create($this->active->modelName);
						$model->populateFromArray($record);
						$model->isInDatabase(TRUE);
						$model->hasChanged(FALSE);
						$pId = $model->isInDatabase() ? $model->getPersistentId() : NULL;
						if($this->isUnique && $pId!==NULL && isset($this->persistentModels[$pId])) {
							return $this->next();
						}
						else {
							$this->models[] = $model;
						}
					}
	
					// If fetching failed then the result set is depleted in which case we
					// should close the cursor and get a model from the next object in the
					// queue
					else {
						$this->active->stmt->closeCursor();
						$this->active->stmt = NULL;
						$this->active = NULL;
						
					}
				}

				// It's another collection
				else if($this->active instanceof ModelCollection) {
					if($this->active->valid() && ($data = $this->active->current())) {
						$this->active->next();
						$pId = $data->isInDatabase() ? $data->getPersistentId() : NULL;
						if($this->isUnique && $pId!==NULL && isset($this->persistentModels[$pId])) {
							return $this->next();
						}
						else {
							$this->models[] = $data;
						}
					}
					else {
						$this->active = NULL;
						$this->next();
					}
				}
			}

			// See if we've got any other queued elements that can be promoted to
			// active
			else if(!empty($this->queue)) {
				$this->active = array_shift($this->queue);
				$this->next();
			}
		}
	}

	public function offsetExists($o) {
		if(isset($this->models[$o])) {
			return TRUE;
		}
		else {
			$this->exhaust();
			return isset($this->models[$o]);
		}
	}

	/**
	* Return the element at position $o, or NULL if it doesn't exist.
	*
	* @return mixed
	*/
	public function offsetGet($o) {
		if(isset($this->models[$o])) {
			return $this->models[$o];
		}
		else {
			$this->exhaust();
			return isset($this->models[$o]) ? $this->models[$o] : NULL;
		}
	}

	public function offsetSet($o, $v) {
		$this->exhaust();
		$this->models[$o] = $v;
	}

	public function offsetUnset($o) {
		$this->exhaust();
		unset($this->models[$o]);
	}


	/**
	* @return void
	*/
	public function rewind() {
		reset($this->models);
	}

	/**
	* Get/set this collection "unique" flag.
	*
	* @param bool State to set
	* @return bool
	*/
	public function unique($state=NULL) {
		return $state===NULL ? $this->isUnique : ($this->isUnique = $state);
	}

	/**
	* Check if there's still gold to be had in them thar hills.
	*
	* @return bool
	*/
	public function valid() {
		if(current($this->models)) {
			return TRUE;
		}
		else if($this->active!==NULL || !empty($this->queue)) {
			$this->next();
			return $this->valid();
		}
	}





}
?>