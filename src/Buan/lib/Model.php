<?php
/**
 * Instances of this class represent a single row in a DB table, akin to the
 * ActiveRecord pattern that every man and his dog likes to employ these days.
 * This allow model->model relationships to be handled with a great degree of
 * control.
 *
 * @package Buan
 */
namespace Buan;

class Model
{

	protected $dbConnectionName = 'default';
	protected $dbData = [];

	protected $dbTableName = null;

	/**
	 * Name of the field(s) that act as the PK for Model's of this type.
	 * For composite keys specify a comma-separated list of all fields in the PK.
	 * For models that don't use a PK, set this to NULL.
	 *
	 * @var string|NULL
	 */
	protected $dbTablePrimaryKey = 'id';


	private $isInDatabase = false;
	private $hasChanged = false;
	private $hasCompositePrimaryKey = false;
	public $modelName = '';

	public $relatives = [];

	/**
	 * Create a Model instance, store it in the Model Repository and return a
	 * reference to that Model. You should ALWAYS use this method rather than the
	 * constructor to create new instances.
	 *
	 * You can invoke this method in one of two ways:
	 *    $m = Model::create('ProductCategory');    // Method #1
	 *        $m = ProductCategoryModel::create();        // Method #2
	 *
	 * The second method only works if you've explicitly defined a
	 * ProductCategoryModel class.
	 *
	 * @param string $modelName Name of the Model you want to create (UpperCamelCaps format)
	 * @return Model
	 */
	static public function create($modelName = null)
	{

		// If a $modelName hasn't been specified then presume the author is using
		// the late static binding method of invocation (method #2 above)
		if ($modelName === null) {
			$modelName = preg_replace("/Model$/i", "", get_called_class());
			$modelName = $modelName == '' ? 'Model' : $modelName;
		}

		// Create and return a new instance of the required Model class.
		// The new instance is also added to the respository as it's non-persistent.
		// We cache the result of the Inflector call to avoid having to call it for
		// every time a model of $modelName is created.
		static $_cache = [];
		$modelClassName = isset($_cache[$modelName]) ? $_cache[$modelName] : Inflector::modelName_modelClass($modelName);
		try {
			$model = new $modelClassName($modelName);
			$_cache[$modelName] = $modelClassName;
			return $model;
		} // If creation fails then fallback to using the core Model class.
		catch (Exception $e) {
			SystemLog::add('Cannot find class for "' . $modelName . '" Model. Using "Model" class instead.', SystemLog::INFO);
			$model = new Model($modelName);
			$_cache[$modelName] = "\Buan\Model";
			return $model;
		}
	}

	/**
	 * Constructor. Never call this method directly. Instead use:
	 *        Buan\Model::create()
	 *
	 * Prepares the Model by setting a few basic properties:
	 *    - The model name
	 *    - The database table name
	 * - The initial primary key value(s) (if applicable)
	 *
	 * @param string $modelName Name of the Model you want to create (UpperCamelCaps format)
	 */
	public function __construct($modelName = null)
	{

		// Store the Model's name
		// If the $modelName hasn't bee specified then we use the calling class'
		// name as the basis of the model name (basically remove the "Model" suffix)
		$this->modelName = $modelName === null ? preg_replace("/Model$/", "", get_class($this)) : $modelName;

		// If the model isn't using it's own class, and it hasn't specified a
		// database table to use for storing it, then do some guess work based on
		// suggested conventions (ie. lower_underscored table names)
		if ($this->dbTableName === null) {
			$this->dbTableName = Inflector::modelName_dbTableName($this->modelName);
		}

		// Determine if this Model has a composite PK. This is considered the case
		// if $this->dbTablePrimaryKey has a comma-separated list of field names. 
		$this->hasCompositePrimaryKey = strpos($this->getPrimaryKey(), ",") !== false;

		// Ensure the PK field(s) are preset to a NULL value.
		// SQLite requires that auto_increment fields have a NULL value in order
		// to increment correctly.
		if ($this->hasCompositePrimaryKey) {
			$keys = explode(",", $this->getPrimaryKey());
			foreach ($keys as $key) {
				$this->setPrimaryKeyValue($key, null);
			}
		} else {
			$this->setPrimaryKeyValue(null);
		}
	}

	/**
	 * Returns the current value of the requested field. If the field doesn't
	 * exist then a NULL entry for it will be created in $this->dbData.
	 *
	 * @param string $fieldName Field name to retrieve
	 * @return mixed
	 */
	public function __get($fieldName)
	{

		// Determine if there is a defined "getter" method for this particular
		// field. The getter will have the format "getFieldName()", eg.
		// "getSerialNumber()".
		// The result of this check is cached to build a field->method mapping to
		// improve the efficiency of future calls.
		// The get_class_methods() call is needed here for case-sensitive matches.
		static $_cache = [];
		$cacheKey = "{$this->modelName}|{$fieldName}";
		if (!isset($_cache[$cacheKey]) || $_cache[$cacheKey] !== false) {
			if (!isset($_cache[$cacheKey])) {
				$methodName = 'get' . Inflector::modelField_classMethod($fieldName);
				if (method_exists($this, $methodName) && in_array($methodName, get_class_methods($this), true)) {
					$_cache[$cacheKey] = $methodName;
					return $this->$methodName();
				} else {
					$_cache[$cacheKey] = false;
				}
			} else {
				return $this->{$_cache[$cacheKey]}();
			}
		}

		// If a value for this field doesn't exist then create and return it
		if (!isset($this->dbData[$fieldName])) {
			return $this->dbData[$fieldName] = null;
		}

		// Return value
		if (is_array($this->dbData[$fieldName])) {
			$this->dbData[$fieldName] = new \ArrayObject($this->dbData[$fieldName], \ArrayObject::ARRAY_AS_PROPS);
			return $this->dbData[$fieldName];
		}
		return $this->dbData[$fieldName];
	}

	/**
	 * Returns TRUE is the given field has been defined in $this->dbData, or FALSE
	 * otherwise.
	 *
	 * @param string $fieldName Field to check
	 * @return bool
	 */
	public function __isset($fieldName)
	{
		return isset($this->dbData[$fieldName]);
	}

	/**
	 * Store the given value in the specified field.
	 * This magic method fowards the request onto a matching "set*()" method, if it
	 * exists, otherwise it simply stores the field value in $this->dbData.
	 *
	 * @param string $fieldName Field name
	 * @param mixed $fieldValue Field value
	 * @return bool Returns TRUE on successful setting, FALSE otherwise
	 */
	public function __set($fieldName, $fieldValue)
	{

		// Create a static cache to hold a list of fields that can simply be
		// directly copied to $this->dbData without further messing, ie. the field
		// isn't a foreign/primary key and there isn't a specific set*() method
		// associated with it.
		// This improves performance by about 60%.
		static $simpleField = [];
		if (isset($simpleField[$this->modelName . '|' . $fieldName])) {
			if (!isset($this->dbData[$fieldName]) || $this->dbData[$fieldName] != $fieldValue) {
				$this->dbData[$fieldName] = $fieldValue;
				$this->hasChanged(true);
			}
			return true;
		}

		// Determine if a PK field is being set in which case forward the call to
		// $this->setprimaryKeyValue() which handles this kind of thing a lot better
		if ($this->hasCompositePrimaryKey() && in_array($fieldName, explode(",", $this->getPrimaryKey()))) {
			return $this->setPrimaryKeyValue($fieldName, $fieldValue);
		} else {
			if ($fieldName === $this->getPrimaryKey()) {
				return $this->setPrimaryKeyValue($fieldValue);
			}
		}

		// Call "set*()" method, if it exists.
		// For this to work, $fieldName must be lower_underscored, eg. some_field,
		// and the corresponding method must be UpperCamelCaps, eg. setSomeField().
		// Otherwise store directly and flag this particular field as being "simple"
		$result = true;
		$methodName = 'set' . Inflector::modelField_classMethod($fieldName);
		if (method_exists($this, $methodName) && in_array($methodName, get_class_methods($this), true)) {
			$oldValue = isset($this->dbData[$fieldName]) ? $this->dbData[$fieldName] : null;
			$result = $this->$methodName($fieldValue);
			if ($this->dbData[$fieldName] != $oldValue) {
				$this->hasChanged(true);
			}
		} else {
			if (!isset($this->dbData[$fieldName]) || $this->dbData[$fieldName] != $fieldValue) {
				$this->dbData[$fieldName] = $fieldValue;
				$this->hasChanged(true);
				$simpleField[$this->modelName . '|' . $fieldName] = true;
				$result = true;
			}
		}

		// TODO:
		// $fieldName might be a foreign-key, so check if there are any Models
		// in $this->relatedModels[M:1] that are linked via $fieldName.
		// If the foreign-key is set to 0 (zero), then we also need to remove
		// that foreign-key's entry in $this->relatedModels.
		//
		// NOTE: You cannot change the ID of a Model that has been loaded from
		// persistent storage.
		//
		// TODO: What about handling foreign-keys that are NOT integers?
		//

		// Catch-all result
		return $result;
	}

	/**
	 * Un-sets the specified entry from $this->dbData.
	 *
	 * @param string $fieldName Field name
	 * @return void
	 */
	public function __unset($fieldName)
	{
		if (isset($this->dbData[$fieldName])) {
			unset($this->dbData[$fieldName]);
		}
	}

	/**
	 * Adds the specified models as relatives of $this model.
	 *
	 * The third argument, $invRelationRef, is only used in the special case of
	 * recursive M:M relationships. If you're adding such a relative then $this
	 * will be linked via the $relationRef reference, and $model will be linked
	 * via the $invRelationRef.
	 *
	 * If adding a M:M relation, the linking model will be returned.
	 *
	 * @param Model|ModelCollection $model Model (s) to add
	 * @param string $relationRef Relationship reference under which models will be added
	 * @param string $invRelationRef Inverse relationship (only required for
	 *        recursive M:M relationships)
	 * @return Model|null
	 */
	public function addRelatives($model, $relationRef = ModelRelation::REF_DEFAULT, $invRelationRef = null)
	{

		// If a collection or an array of models has been specified, add each one
		// individually
		if (is_array($model) || $model instanceof ModelCollection) {
			/** @var Model $singleModel */
			foreach ($model as $singleModel) {
				$this->addRelatives($singleModel, $relationRef, $invRelationRef);
			}
			return null;
		}
		// Determine the relationship between $this and $model
		$relation = ModelRelation::getRelation($this->modelName, $model->modelName, $relationRef);
		if ($invRelationRef !== null) {
			// $invRelation = ModelRelation::getRelation($this->modelName, $model->modelName, $invRelationRef);
		}

		// M:M
		// The idea behind M:M relationships is to split them into a 1:M and
		// corresponding M:1 relationships, with a linking model on the M-side of
		// both those relationships.
		if ($relation->isManyToMany()) {

			// Create an instance of the linking model
			$linkModel = Model::create($relation->getLinkModel());

			// If $this and $model are both persistent then there's chance that a
			// linking model also exists in the db, so look for it. We also look at
			// in-memory Models already related to $this to see if any of them fit the
			// request
			/*$c = new ModelCriteria();
			$c->addClause(ModelCriteria::EQUALS, $linkModel->getForeignKey($this), $this->getPrimaryKeyValue());
			$c->addClause(ModelCriteria::EQUALS, $linkModel->getForeignKey($model), $model->getPrimaryKeyValue());
			$links = $this->findRelatives($linkModel->modelName, $c, $relation->getReference());
			if(!$links->isEmpty()) {
				// A link already exists so don't do anything
				return;
			}*/

			// TODO: Have commented out the above, so it's up to the user to first
			// determine if another linking model already exists that satisfies the
			// relationships. HOWEVER, we need to implement a check if there is a
			// "limit" on the M:M relationship - ie. if only one link can exist
			// between 2 models then enforce that limit.

			// Nope? Ok, if the relationship is non-recursive then create simple
			// reference to each other
			if (!$relation->isRecursive()) {
				$linkModel->addRelatives($this, $relation->getReference());
				$linkModel->addRelatives($model, $relation->getReference());
			}

			// If the relationship is recursive then we've got to do a few more
			// complicated things.
			else {

				// Really the caller should have specified both a relation reference (in
				// $relationRef) AND the inverse of that relation (in $invRelationRef),
				// so first we presume they did!
				if ($invRelationRef !== null) {
					$this->addRelatives($linkModel, $relationRef);
					$model->addRelatives($linkModel, $invRelationRef);
				}

				// But if they didn't then we may still be able to create associations
				// if the $relationRef is one of the "default" references (REF_PARENT,
				// or REF_CHILD)
				else {
					if ($relationRef === ModelRelation::REF_PARENT) {
						$this->addRelatives($linkModel, ModelRelation::REF_PARENT);
						$model->addRelatives($linkModel, ModelRelation::REF_CHILD);
					} else {
						if ($relationRef === ModelRelation::REF_CHILD) {
							$this->addRelatives($linkModel, ModelRelation::REF_CHILD);
							$model->addRelatives($linkModel, ModelRelation::REF_PARENT);
						} // Otherwise, tell them off
						else {
							SystemLog::add('When adding a related Model via a M:M relationship, you must specify the reverse relation reference (usually REF_PARENT or REF_CHILD).', SystemLog::CORE);
						}
					}
				}
			}
			return $linkModel;
		}

		// 1:M (and 1:1, ie. 1:M,1)
		if ($relation->isOneToMany()) {

			// If $model is already related to $this, don't continue
			$cd = ModelRelation::MANY_TO_ONE;
			$tm = $this->modelName;
			$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
			if (isset($model->relatives[$cd][$tm][$rr]) && $model->relatives[$cd][$tm][$rr] === $this) {
				return null;
			}
			unset($cd, $tm, $rr);

			// If $this is persistent then simply update $model's FK accordingly. This
			// will implicitly flag $model as "hasChanged" so it will be added to the
			// Model Repository, if it's not already there.
			if ($this->isInDatabase()) {
				$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
				//$rr = $relation->getInverseRelation()->getReference();
				$model->setForeignKeyValue($this->modelName, $this->getPrimaryKeyValue(), $rr);
				$model->relatives[ModelRelation::MANY_TO_ONE][$this->modelName][$rr] = $this;
				$this->relatives[ModelRelation::ONE_TO_MANY][$model->modelName][$relationRef][] = $model;
			}

			// If $this is non-persistent then we instead need to store a reference
			// to $this within $model so when it comes to saving $model it's FK will
			// be set to $this' PK (when it in turn is saved).
			// If $model already contains a reference to a previously added relative
			// in the same FK then that relationship is disbanded first.
			else {
				$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
				//$rr = $relation->getInverseRelation()->getReference();
				$model->setForeignKeyValue($this->modelName, null, $rr);
				$model->relatives[ModelRelation::MANY_TO_ONE][$this->modelName][$rr] = $this;
				$this->relatives[ModelRelation::ONE_TO_MANY][$model->modelName][$relationRef][] = $model;
			}

			// If there is a limit on the M-side the shift older relatives off the
			// front of the list and disband the ties between $this model and those
			// that have been removed.
			if (($limit = $relation->getLimit()) !== null) {
				$disbanded = array_slice(
					$this->relatives[ModelRelation::ONE_TO_MANY][$model->modelName][$relationRef],
					0,
					-$limit
				);
				$this->relatives[ModelRelation::ONE_TO_MANY][$model->modelName][$relationRef] = array_slice(
					$this->relatives[ModelRelation::ONE_TO_MANY][$model->modelName][$relationRef], -$limit);
				$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
				/** @var Model $singleModel */
				foreach ($disbanded as $singleModel) {
					$singleModel->setForeignKeyValue($this->modelName, null, $rr);
					unset($singleModel->relatives[ModelRelation::MANY_TO_ONE][$this->modelName][$rr]);
				}
			}
			return null;
		}

		// M:1
		if ($relation->isManyToOne()) {

			// If $model is already related to $this, don't continue
			$cd = ModelRelation::MANY_TO_ONE;
			$tm = $model->modelName;
			//$rr = $relation->getInverseRelation()->getReference();
			$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
			if (isset($this->relatives[$cd][$tm][$rr]) && $this->relatives[$cd][$tm][$rr] === $model) {
				return null;
			}
			unset($cd, $tm, $rr);

			// If $model is persistent then simply set $this' FK to $model's PK value
			if ($model->isInDatabase()) {
				$this->setForeignKeyValue($model->modelName, $model->getPrimaryKeyValue(), $relationRef);
				$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
				//$rr = $relation->getInverseRelation()->getReference();
				$model->relatives[ModelRelation::ONE_TO_MANY][$this->modelName][$rr][] = $this;
				$this->relatives[ModelRelation::MANY_TO_ONE][$model->modelName][$relationRef] = $model;
			}

			// If $model is non-persistent then we instead need to store a reference
			// to $model in $this
			else {
				$this->setForeignKeyValue($model->modelName, null, $relationRef);
				$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
				//$rr = $relation->getInverseRelation()->getReference();
				$model->relatives[ModelRelation::ONE_TO_MANY][$this->modelName][$rr][] = $this;
				$this->relatives[ModelRelation::MANY_TO_ONE][$model->modelName][$relationRef] = $model;
			}

			// If there is a limit on the M-side then shift older relatives off the
			// front of the list and disband the ties between $model and those that
			// have been removed.
			if (($limit = $relation->getInverseRelation()->getLimit()) !== null) {
				$disbanded = array_slice($model->relatives[ModelRelation::ONE_TO_MANY][$this->modelName][$relationRef], 0, -$limit);
				$model->relatives[ModelRelation::ONE_TO_MANY][$this->modelName][$relationRef] = array_slice(
					$model->relatives[ModelRelation::ONE_TO_MANY][$this->modelName][$relationRef], -$limit);
				$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
				/** @var Model $singleModel */
				foreach ($disbanded as $singleModel) {
					$singleModel->setForeignKeyValue($model->modelName, null, $rr);
					unset($singleModel->relatives[ModelRelation::MANY_TO_ONE][$model->modelName][$rr]);
				}
			}
		}
		return null;
	}

	/**
	 * Disbands the relationships between $this and all specified Models.
	 *
	 * If a relationship reference is given (in $relationRef) then only the Models
	 * on that side of the relationship will be disowned.
	 *
	 * It's important to note that only in-memory relationships will be broken,
	 * nothing is committed to persistent storage. So it's up to the caller to
	 * save each affected model as necessary.
	 *
	 * @param Model|ModelCollection|array $model Model (s) to disown
	 * @param string $relationRef Relationship reference to use when disowning
	 * @return ModelCollection
	 */
	public function disownRelatives($model, $relationRef = null)
	{

		// If a collection or an array of models has been specified, disown each one
		// individually
		$linkCollection = new ModelCollection();
		if (is_array($model) || $model instanceof ModelCollection) {
			foreach ($model as $m) {
				$linkCollection->append($this->disownRelatives($m, $relationRef));
			}
			return $linkCollection;
		}

		// Handle multiple relationship references by calling each one
		// individually to remove $model from all relationships
		$relation = ModelRelation::getRelation($this->modelName, $model->modelName, $relationRef);
		if (is_array($relation)) {
			foreach ($relation as $rel) {
				$linkCollection->append($this->disownRelatives($model, $rel->getReference()));
			}
			return $linkCollection;
		}

		// Act on relationship
		$relationRef = $relation->getReference();
		switch ($relation->getCardinality()) {

			// No relationship
			case ModelRelation::NONE:
				break;

			// 1:M
			case ModelRelation::ONE_TO_MANY:

				// Remove relatives and unset FK
				$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
				$model->setForeignKeyValue($this->modelName, null, $rr);
				unset($model->relatives[ModelRelation::MANY_TO_ONE][$this->modelName][$rr]);
				if (isset($this->relatives[ModelRelation::ONE_TO_MANY][$model->modelName][$relationRef]) && ($key = array_search($model, $this->relatives[ModelRelation::ONE_TO_MANY][$model->modelName][$relationRef])) !== false) {
					unset($this->relatives[ModelRelation::ONE_TO_MANY][$model->modelName][$relationRef][$key]);
				}

				// Done
				break;

			// M:1
			case ModelRelation::MANY_TO_ONE:

				// Remove relatives and unset FK
				$rr = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relationRef;
				$cd = ModelRelation::MANY_TO_ONE;
				$tm = $model->modelName;
				//$rr = $relation->getInverseRelation()->getReference();
				$this->setForeignKeyValue($tm, null, $relationRef);
				unset($this->relatives[$cd][$tm][$relationRef]);
				if (isset($model->relatives[ModelRelation::ONE_TO_MANY][$this->modelName][$rr]) && ($key = array_search($this, $model->relatives[ModelRelation::ONE_TO_MANY][$this->modelName][$rr])) !== false) {
					unset($model->relatives[ModelRelation::ONE_TO_MANY][$this->modelName][$rr][$key]);
				}
				unset($cd, $tm, $rr);

				// Done
				break;

			// M:M
			case ModelRelation::MANY_TO_MANY:

				// Break the relationships between each linking model that links $this
				// with $model
				$linkRelation = ModelRelation::getRelation($this->modelName, $relation->getLinkModel());
				$scd = ModelRelation::ONE_TO_MANY;
				$stm = $linkRelation->getTargetModel();
				$srr = $linkRelation->getReference();
				if (isset($this->relatives[$scd][$stm][$srr])) {
					/** @var Model $link */
					foreach ($this->relatives[$scd][$stm][$srr] as $link) {
						$tcd = ModelRelation::MANY_TO_ONE;
						$ttm = $relation->getTargetModel();
						$trr = $linkRelation->getInverseRelation()->getReference();
						if (isset($link->relatives[$tcd][$ttm][$trr]) && $link->relatives[$tcd][$ttm][$trr] === $model) {
							$this->disownRelatives($link, $srr);
							$link->disownRelatives($model, $trr);
						}
					}
				}

				// As well as the above, return a list of persistent linking models that
				// should be removed in order to disband the relationship between $this
				// and $model
				$linkCollection->append($this->findLinkingRelatives($model));

				// Done
				break;

			// Default
			default:
				break;
		}

		return $linkCollection;
	}

	/**
	 * Returns a collection of "linking models" that link $this with $model.
	 *
	 * @param Model $model Find models that link $this with $model
	 * @param string|ModelCriteria $criteriaOrRelationRef
	 * @param string $relationRef
	 * @return ModelCollection|array
	 */
	public function findLinkingRelatives($model, $criteriaOrRelationRef = null, $relationRef = null)
	{

		$criteria = new ModelCriteria();
		if (is_string($criteriaOrRelationRef)) {
			$relationRef = $criteriaOrRelationRef;
		} else {
			if ($criteriaOrRelationRef instanceof ModelCriteria) {
				$criteria = $criteriaOrRelationRef;
			}
		}

		$relation = ModelRelation::getRelation($this->modelName, $model->modelName, $relationRef);
		if (is_array($relation)) {
			$results = [];
			foreach ($relation as $r) {
				$results[$r->getReference()] = $this->findLinkingRelatives($model, $criteriaOrRelationRef, $r->getReference());
			}
			return $results;
		}

		$linkModel = Model::create($relation->getLinkModel());

		//$criteria->addClause(ModelCriteria::EQUALS, $linkModel->getForeignKey($this), $this->getPrimaryKeyValue());
		$criteria->addClause(ModelCriteria::EQUALS, $linkModel->getForeignKey($model), $model->getPrimaryKeyValue());
		return $this->findRelatives($linkModel->modelName, $criteria, $relation->getReference());
	}


	/**
	 * Find all relatives of $this Model according to the given arguments.
	 *
	 * In most cases this method will return a ModelCollection instance.
	 * However, in some cases it will return an indexed array, depending on which
	 * arguments have been omitted/included.
	 *
	 * For example, if you do not specify a $modelName then an array of several
	 * ModelCollections may be returned, indexed by the model names of all related
	 * models.
	 *
	 * Also, if you do not specify a relationship reference when finding relatives
	 * in a recursive relationship then you can expect to be given an array of
	 * ModelCollections indexed by each relationship reference.
	 *
	 * If given, the second argument is either a ModelCriteria instance to filter
	 * the results, or a relationship reference.
	 *
	 * @param string $modelName Find relatives of this Model type
	 * @param ModelCriteria|string ** see notes above **
	 * @param string $relationRef Relationship reference
	 * @return ModelCollection|array
	 */
	public function findRelatives($modelName = null, $criteriaOrRelationRef = null, $relationRef = null)
	{

		// No specific model name has been specified so we need to build a
		// collection of Models from ALL relationships with $this Model.
		// In this case the returned value is an array indexed by model name. If
		// multiple relationship references exist between $this and $modelName (such
		// as child/parent in recursive relationships) then each array element is
		// further broken down into an array indexed by the relationship reference.
		if ($modelName === null) {
			$collection = [];
			$relations = ModelRelation::getRelation($this->modelName);
			/** @var ModelRelation $relation */
			foreach ($relations as $relation) {
				$tm = $relation->getTargetModel();
				$rr = $relation->getReference();
				if (isset($collection[$tm])) {
					if (isset($collection[$tm][$rr])) {
						$collection[$tm][$rr]->append($this->findRelatives($tm, $rr));
					} else {
						$collection[$tm][$rr] = $this->findRelatives($tm, $rr);
					}
				} else {
					$collection[$tm] = $this->findRelatives($tm, $rr);
				}
			}
			return $collection;
		}

		// A specific model name has been specified.
		// First of all, determine if a relationship reference has been specified
		// and use that throughout, otherwise get all possible relations and
		// recursively call $this->findRelatives() for each one.
		if (is_string($criteriaOrRelationRef)) {
			$criteria = null;
			$relationRef = $criteriaOrRelationRef;
		} else {
			if ($criteriaOrRelationRef instanceof \Buan\ModelCriteria) {
				$criteria = $criteriaOrRelationRef;
			} else {
				$criteria = null;
			}
		}
		$relation = ModelRelation::getRelation($this->modelName, $modelName, $relationRef);

		// If multiple relations are available between $this and $modelName, and the
		// caller hasn't specified which of these relationships to use (ie.
		// $relationRef===NULL) then assume we're dealing with a recursive
		// relationship and return an array of ModelCollections for each
		// relationship, indexed by the relationship reference.
		if (is_array($relation)) {
			if (isset($relation[ModelRelation::REF_DEFAULT]) && count($relation) == 1) {
				$relation = $relation[ModelRelation::REF_DEFAULT];
			} else {
				$collection = [];
				foreach ($relation as $ref => $rel) {
					$collection[$ref] = $this->findRelatives($modelName, $criteria, $ref);
				}
				return $collection;
			}
		}

		// So by now we should be dealing with a specific model name, some criteria
		// (if defined) and a specific relationship reference. So let's do some
		// loading and retrieving from the db with respect to the cardinality of the
		// relationship.
		switch ($relation->getCardinality()) {

			/* No relationship */
			case ModelRelation::NONE:
				break;

			/* 1:1 */
			case ModelRelation::ONE_TO_ONE:

				// Such relations are held as 1:M,1 relations internally so it'll be
				// handled by the 1:M case below.
				break;

			/* 1:M */
			case ModelRelation::ONE_TO_MANY:

				// First, gather all the in-memory relatives attached to $this model.
				// If $criteria has been specified then we also check that these models
				// satisfy those criteria.
				$cd = ModelRelation::ONE_TO_MANY;
				$tm = $relation->getTargetModel();
				$rr = $relation->getReference();
				if (isset($this->relatives[$cd][$tm][$rr])) {
					$collection = new ModelCollection($this->relatives[$cd][$tm][$rr]);
					if ($criteria !== null) {
						// TODO: ::applyTo() isn't implemented yet! so this will create an empty collection every time
						$collection = $criteria->applyTo($collection);
					}
				} else {
					$collection = new ModelCollection();
				}

				// If $this model is in the db then query the db to find all matching
				// relatives and append them to the collection.
				// TODO: Need to handle composite or NULL PKs
				if ($this->isInDatabase()) {

					// With no specific criteria to follow we'll load ALL relatives from
					// persistent storage
					$targetModelName = $relation->getTargetModel();
					$targetModel = Model::create($targetModelName);
					if ($criteria === null) {
						$pkv = $this->{$relation->getNativeKey()}; //$this->getPrimaryKeyValue();
						$c = new ModelCriteria();
						$c->addClause(ModelCriteria::EQUALS,
							"`{$targetModel->getDbTableName()}`." . $targetModel->getForeignKey($this, $relation->getReference()),
							$pkv
						);
						try {
							$collection->append(ModelManager::select($targetModelName, $c));
						} catch (Exception $e) {
						}
					}

					// But if given some criteria then we need to apply it, but modify it
					// a little to ensure it includes the foreign-key clause
					else {
						$pkv = $this->{$relation->getNativeKey()}; //$this->getPrimaryKeyValue();
						$criteria->addClause(ModelCriteria::EQUALS,
							"`{$targetModel->getDbTableName()}`." . $targetModel->getForeignKey($this, $relation->getReference()),
							$pkv
						);
						$collection->append(ModelManager::select($targetModelName, $criteria));
					}
				}

				// For recursive 1:M relationships we now need to load Models on the
				// M:1 side of the relationship in order to establish a link between
				// the loaded instance on the 1 and M sides, so don't break and instead
				// pass through to the next switch case ...
				if ($relation->isRecursive()) {
					$relation = $relation->getInverseRelation();
					$loadingTheInverse = true;
				} else {
					return $collection;
					break;
				}

			/* M:1 */
			case ModelRelation::MANY_TO_ONE:

				// First, check if an in-memory model exists on the 1-side of this
				// relationship
				$cd = ModelRelation::MANY_TO_ONE;
				$tm = $relation->getTargetModel();
				$rr = $relation->getReference();
				$col = new ModelCollection();
				if (isset($this->relatives[$cd][$tm][$rr])) {
					$col = new ModelCollection($this->relatives[$cd][$tm][$rr]);
				}

				// And if not, and the foreign keys are not NULL or 0 (zero), then
				// attempt to load the 1-side from persistent storage.
				// TODO: Implement support for composite FKs
				else {
					$fk = $this->getForeignKey($tm, $rr);
					//if($this->{$fk}!==NULL && $this->{$fk}!==0 && $this->{$fk}!=='0') {
					if (!empty($this->{$fk})) {

						// If the foreign key does not match the target model's primary key
						// do some manual labour
						if ($relation->getForeignKey() !== Model::create($tm)->getPrimaryKey()) {
							$criteria = $criteria === null ? new ModelCriteria() : $criteria;
							$target = Model::create($tm);
							$criteria->addClause(ModelCriteria::EQUALS, $target->getDbTableName() . '.' . $relation->getForeignKey(), $this->{$fk});
							$col = ModelManager::select($tm, $criteria);
						} // Otherwise, load the single parent using the PK->FK match
						else {
							$target = Model::create($tm);
							$criteria = $criteria === null ? new ModelCriteria() : $criteria;
							$criteria->addClause(ModelCriteria::EQUALS, $target->getDbTableName() . '.' . $target->getPrimaryKey(), $this->{$fk});
							$col = ModelManager::select($tm, $criteria);
							if (!$col->isEmpty()) {
								$target = $col->get(0);
								/*$target = Model::create($tm);
                                $mmTarget = ModelManager::create($tm);
                                $target->setPrimaryKeyValue($this->{$fk});
                                if($mmTarget->load($target)) {
                                    $col = new ModelCollection($target);*/

								// If the relationship is recursive, then remember we can only
								// add recursive related Models via 1:M relationships, so we have
								// to reverse the way we add a related Model here.
								if ($relation->isRecursive()) {
									$ir = $relation->getInverseRelation();
									$cd = $ir->getCardinality();
									$target->relatives[$cd][$tm][$ir->getReference()][] = $this;
								} else {
									$this->relatives[$cd][$tm][$rr] = $target;
								}
							}
						}
					}
				}

				// If this switch case has been arrived at by the 1:M case not breaking
				// then return the 1:M collection, other return the M:1 collection
				return empty($loadingTheInverse) ? $col : $collection;
				break;

			/* M:M */
			case ModelRelation::MANY_TO_MANY:

				/*// First, find any in-memory instances of the target model (via linking-
				// model instances)
				$linkRelation = ModelRelation::getRelation($this->modelName, $relation->getLinkModel(), $relation->getReference());
				$scd = ModelRelation::ONE_TO_MANY;
				$stm = $linkRelation->getTargetModel();
				$srr = $linkRelation->getReference();
				$collection = new ModelCollection();
				if(isset($this->relatives[$scd][$stm][$srr])) {
					foreach($this->relatives[$scd][$stm][$srr] as $link) {
						$tcd = ModelRelation::MANY_TO_ONE;
						$ttm = $relation->getTargetModel();
						$trr = $linkRelation->getInverseRelation()->getReference();
						if(isset($link->relatives[$tcd][$ttm][$trr])) {
							$collection->append(new ModelCollection($link->relatives[$tcd][$ttm][$trr]));
						}
					}
				}

				// TODO: Look in DB for more links*/


				// First, find all instances of the linking model that are related to
				// $this model
				$links = $this->findRelatives($relation->getLinkModel(), $relation->getReference());

				// Now find all instances of the target model that are related to each
				// of the linking models we just found
				$collection = new ModelCollection();
				$r = $relation->isRecursive() ? $relation->getInverseRelation() : $relation;
				foreach ($links as $l) {
					$crt = $criteria !== null ? clone $criteria : null;
					$collection->append($l->findRelatives($r->getTargetModel(), $crt, $r->getReference()));
				}

				// Result
				return $collection;
				break;


				/* THE FOLLOWING HAS JUST BEEN COPIED OVER FROM OLD MODEL CLASS, SO GO THROUGH
                IT AND PICK OUT WHAT'S NEEDED AS NECESSARY */
				// Find any linking models in the db
				$c = new ModelCriteria();
				$c->addClause(ModelCriteria::EQUALS, $linkModel->getDbTableName() . '.' . $linkModel->getForeignKey($this), $this->getPrimaryKeyValue());
				$c->addClause(ModelCriteria::EQUALS, $linkModel->getDbTableName() . '.' . $linkModel->getForeignKey($model), $model->getPrimaryKeyValue());
				$collection = ModelManager::select($linkModel->modelName, $c);

				if (!$links->isEmpty()) {
					// A link already exists so don't do anything
					return;
				}


				// First of all we need to load all instances of the Model that links
				// the source Model type to the target Model type in this relationship.
				// The $criteria is ignored at this point, because it (should) applies
				// to the target model, NOT the linking model.
				$linkModelName = $relation->getLinkModel();
				$linkForeignKey = Model::create($linkModelName)->getForeignKey($this, $relation->getReference());
				$linkDbTableName = Model::create($linkModelName)->getDbTableName();
				$c = new ModelCriteria();
				$c->addClause(ModelCriteria::EQUALS, $linkDbTableName . '.' . $linkForeignKey, $this->getPrimaryKeyValue());
				$linkModels = ModelManager::select($linkModelName, $c);

				// TODO: WHAT IS THIS BIT FOR? COMMENTS JAMES, USEFUL COMMENTS!!
				// Move all Models from $this->relatedModels that match entries
				// in $linkModels into $linkModels.
				foreach ($linkModels as $k => $lModel) {
					$relatedModel = $this->getRelatedModelByInstance($lModel);
					if (!is_null($relatedModel)) {
						$linkModels[$k] = $relatedModel;
					}
				}

				// Get all instances of target model
				// If the relationship is recursive then we need to get
				// models on the reverse side of the relationship, ie.
				// REF_PARENT/REF_CHILD.
				$ref = $relation->isRecursive() ? $relation->getInverseRelation()->getReference() : $relation->getReference();
				$tModelName = $relation->getTargetModel();
				$tModelManager = ModelManager::create($tModelName);
				$lForeignKey = Model::create($linkModelName)->getForeignKey($tModelName, $ref);
				foreach ($linkModels as $linkModel) {
					$tModel = Model::create($tModelName);
					$tModel->setPrimaryKeyValue($linkModel->{$lForeignKey});
					if ($tModelManager->load($tModel)) {
						// TODO: Check that $tModel is not already loaded into $linkModel, and $linkModel is not already loaded into $this
						$linkModel->addRelatives($tModel, $ref);
						$this->addRelatives($linkModel, $rel->getReference());
					}
					unset($tModel);
				}

				// Now retrieve
				if ($relation->isRecursive()) {
					$allLinkModels = $this->findRelatives($relation->getLinkModel());
					foreach ($allLinkModels as $ref => $linkModels) {
						foreach ($linkModels as $linkModel) {
							$rModels = $linkModel->findRelatives($modelName);
							unset($rModels[$relationRef]);
							foreach ($rModels as $rModel) {
								if ($rModel !== $this) {
									$collection->append($rModel);
								}
							}
						}
					}
				} else {
					$linkModelName = $relation->getLinkModel();
					if (isset($this->relatives[ModelRelation::ONE_TO_MANY][$linkModelName][$relation->getReference()])) {
						$linkModels = $this->relatives[ModelRelation::ONE_TO_MANY][$linkModelName][$relation->getReference()];
						foreach ($linkModels as $linkModel) {
							$collection->append($linkModel->findRelatives($modelName, $relation->getReference()));
						}
					}
				}
				break;

			/* Unknown case */
			default:
				break;
		}
	}

	/**
	 * Returns the name of the database connection used by this Model.
	 *
	 * @return string
	 */
	public function getDbConnectionName()
	{
		return $this->dbConnectionName;
	}

	/**
	 * Returns the current contents of this Model's data fields.
	 *
	 * @return array
	 */
	public function getDbData()
	{
		return $this->dbData;
	}

	/**
	 * Returns the name of the database table in which Model's of this type are
	 * stored.
	 *
	 * @return string
	 */
	public function getDbTableName()
	{
		return $this->dbTableName;
	}

	/**
	 * Returns the name of the FK field used by $this Model to point to Models of
	 * type $model.
	 *
	 * If you don't use the recommended method of naming foreign-keys
	 * (ie. "foreign_table_id") on $this Model, then you can override this method
	 * to return customized foreign-keys.
	 *
	 * @param string|Model Model to which the foreign-key points
	 * @param string Relationship reference
	 * @return string
	 */
	public function getForeignKey($model, $relationRef = ModelRelation::REF_DEFAULT)
	{

		// TODO: Add a result cache here

		// Get Model name if passed an instance
		if ($model instanceof Model) {
			$model = $model->modelName;
		}

		// Get the relationship between $this and $model
		$rel = ModelRelation::getRelation($this->modelName, $model, $relationRef);

		// Normal relation
		return $rel->isOneToMany() ? $rel->getForeignKey() : $rel->getNativeKey();
	}

	/**
	 * Returns an instance of the ModelManager that is used to manage Models of
	 * $this type.
	 *
	 * @return \Buan\ModelManager
	 */
	function getModelManager()
	{
		return ModelManager::create($this->modelName);
	}

	/**
	 * Returns a value that uniquely identifies this Model amongst other Models of
	 * the same type. This is primarily for returning a unique ID for models that
	 * either use a composite PK or don't have a PK at all.
	 *
	 * This value is NOT necessarily stored in the DB. For example, it could be a
	 * hash of certain field values.
	 *
	 * @return string
	 */
	public function getPersistentId()
	{
		$pkValue = $this->getPrimaryKeyValue();
		return is_array($pkValue) ? implode('+', $pkValue) : $pkValue;
	}

	/**
	 * Returns the name of this Model's primary-key field.
	 * If the primary-key is composite then this will return a comma-separated
	 * string of all field names used in the key.
	 *
	 * @return string
	 */
	public function getPrimaryKey()
	{
		return $this->dbTablePrimaryKey;
	}

	/**
	 * Returns the current value in this Model's PK field(s).
	 * If the primary-key is composite, then this method will return an array
	 * of "field-name=>field-value" pairs.
	 *
	 * @return mixed
	 */
	public function getPrimaryKeyValue()
	{
		$pk = $this->getPrimaryKey();
		if (!$this->hasCompositePrimaryKey()) {
			return $this->{$pk};
		} else {
			$pk = explode(",", $pk);
			$pkValue = [];
			foreach ($pk as $fieldName) {
				$pkValue[$fieldName] = $this->{$fieldName};
			}
			return $pkValue;
		}
	}

	/**
	 * Returns the current value of $this->hasChanged, or sets it to the
	 * specified value.
	 *
	 * @param bool Set the hasChanged flag to this value
	 * @return bool
	 */
	public function hasChanged($hasChanged = null)
	{
		return $hasChanged === null ? $this->hasChanged : ($this->hasChanged = $hasChanged);
	}

	/**
	 * Returns TRUE if this Model uses a composite primary key.
	 *
	 * @return void
	 */
	public function hasCompositePrimaryKey()
	{
		return $this->hasCompositePrimaryKey;
	}

	/**
	 * Returns the current value of $this->isInDatabase or sets it to the
	 * specified value.
	 *
	 * @param bool Set the inDatabase flag to this value
	 * @return bool
	 */
	public function isInDatabase($isInDatabase = null)
	{
		return $isInDatabase === null ? $this->isInDatabase : ($this->isInDatabase = $isInDatabase);
	}

	/**
	 * Quick method for populating this Model's fields from values in a given
	 * array.
	 * Note that this will in turn be calling the __set() method to store the
	 * value for each field so it's not as quick as assigning values directly to
	 * variables.
	 *
	 * @param array Array in fieldName=>fieldValue pairs
	 * @return void
	 */
	public function populateFromArray($data)
	{
		foreach ($data as $k => $v) {
			$this->{$k} = $v;
		}
	}

	/**
	 * Sets the value(s) of the FK field(s) that point to the target model.
	 *
	 * If the target model uses a composite PK, then the corresponding FK in $this
	 * model should also be composite. In this case $value should be an array
	 * indexed by field name.
	 *
	 * @param string Target model name
	 * @param Model|array|string Value (s) to which $this FK will be set
	 * @param string Relationship reference to help determine which FK to use
	 * @return void
	 */
	public function setForeignKeyValue($modelName, $value, $relationRef = ModelRelation::REF_DEFAULT)
	{

		// Handle composite key
		if (is_array($value)) {
			// TODO: How do we map the field names in $value to the field names in
			// $this model?
		} // Handle normal key
		else {
			$fk = $this->getForeignKey($modelName, $relationRef);
			$this->{$fk} = $value;
		}
	}

	/**
	 * Sets this Model's PK value.
	 * Persistent Models cannot have their primary key changed.
	 *
	 * This method will be automatically executed (via __set()) if the
	 * primary-key field is altered directly. For example, if the PK is "id", then
	 * the following are equivalent:
	 *        $this->id = 5;
	 *        $this->setPrimaryKeyValue(5);
	 *
	 * For a composite PK, there are two methods of setting the values for each
	 * field involved in the key:
	 *        $this->setPrimaryKeyValue(array(field1=>value, field2=>value));, or
	 *        $this->setPrimaryKeyValue(field1, value);
	 *        $this->setPrimaryKeyValue(field2, value);
	 *
	 * @param string|mixed See description above
	 * @param mixed Value of specified composite key field
	 * @return bool
	 */
	public function setPrimaryKeyValue($arg1, $arg2 = null)
	{

		// Check that $this Model is not persistent.
		if ($this->isInDatabase()) {
			// TODO: What about the hasChanged flag? it was set to true by __set(), but we might need it false here.
			//	Maybe move this method's code to __set and have a special case "... if($fieldName=="id") { ... } ..."
			SystemLog::add("Attempting to reset primary-key on a persistent Model", SystemLog::WARNING);
			return false;
		} // Handle standard primary key
		else {
			if (!$this->hasCompositePrimaryKey()) {
				$this->dbData[$this->getPrimaryKey()] = $arg1;
			} // Handle composite primary key
			else {
				if (is_array($arg1)) {
					// TODO: Should we ensure that all array keys in $arg1 are actually primary key fields?
					foreach ($arg1 as $k => $v) {
						$this->dbData[$k] = $v;
					}
				} else {
					if ($arg2 !== null) {
						$this->dbData[$arg1] = $arg2;
					} else {
						// TODO: THROW exception? would be better
					}
				}
			}
		}

		// Flag as changed
		$this->hasChanged(true);
		return true;
	}
}

?>
