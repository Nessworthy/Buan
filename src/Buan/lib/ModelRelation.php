<?php
/**
 * @package Buan
  */
namespace Buan;

class ModelRelation
{

    /*
     * @constant int NONE
     * Describes a non-existent relationship.
     */
    const NONE = 0;

    /*
     * @constant int ONE_TO_ONE
     * Describes a 1:1 relationship.
     */
    const ONE_TO_ONE = 1;

    /*
     * @constant int ONE_TO_MANY
     * Describes a 1:M relationship.
     */
    const ONE_TO_MANY = 2;

    /*
     * @constant int MANY_TO_ONE
     * Describes a M:1 relationship.
     */
    const MANY_TO_ONE = 3;

    /*
     * @constant int MANY_TO_MANY
     * Describes a M:M relationship.
     */
    const MANY_TO_MANY = 4;

    /*
     * @constant string REF_DEFAULT
     * Default reference to use in relationships.
     */
    const REF_DEFAULT = '__default__';

    /*
     * @constant string REF_PARENT
     * Reference used in the 1:M side of a recursive 1:M relationship.
     * This is just a convenience alias for self::REF_DEFAULT, and is meant
     * to provide code clarity in some situations.
     */
    const REF_PARENT = '__default__';

    /*
     * @constant string REF_CHILD
     * Reference used in the M:1 side of a recursive 1:M relationship.
     */
    const REF_CHILD = '__child__';

    /*
     * @property int $cardinality
     * The cardinality of $this relationship (one of the class constants)
     */
    private $cardinality = null;

    /*
     * @property bool isRecursive
     * Indicates if this is a recursive relationship.
     * A recursive relationship is when a Model has a foreign-key pointing to an
     * instance of the same Model. This would be common in a nested directory
     * structure, for example.
     */
    private $isRecursive = false;

    /*
     * @property int $limit
     * In a 1:M relationship, this defines the limit of Models on the "M" side of
     * the relationship
     */
    private $limit = null;

    /*
     * @property string $modelSource
     * The name of the Model on the LEFT of a relationship (ie. the "1" in a
     * "1:M" relationship)
     */
    private $modelSource = null;

    /*
     * @property string $modelTarget
     * The name of the Model on the RIGHT of a relationship (ie. the "M" in a
     * "1:M" relationship)
     */
    private $modelTarget = null;

    /*
     * @property string $modelLink
     * The name of the Model that links $modelSource and $modelTarget in a M:M
     * relationship.
     * Can also be used in 1:M and M:1 relationships that are part of a M:M
     * relationship to hold the name of the Model that features in the other
     * "half" of the relationship.
     */
    private $modelLink = null;

    /*
     * @property string $reference
     * Custom reference for this relationship.
     * Used if several table columns from Model A all point to Model B.
     */
    private $reference = null;

    /*
     * @property string $nativeKey
     * Holds the name of the field involved in the LEFT side of the relationship.
     */
    private $nativeKey = null;

    /*
     * @property string $foreignKey
     * Hodls the name of the field involved in the RIGHT side of the relationship.
     */
    private $foreignKey = null;

    /*
     * @property array $options
     * Holds a list of options that have been set on this relationship.
     * Valid options are:
     * nocascade	= Used in 1:M relationships. Prevents the Model instances on the
     * "M" side from being deleted when the "1" side is deleted.
     */
    private $options = [];

    /*
     * @property array $relationships
     * Stores ModelRelation objects that describe the relationships between any
     * two Models.
     * This is a multi-dimensional array with indexes in the format:
     *	['model-source']['model-target'] = ModelRelation instance
     * eg:
     *	['Book']['Author'] = [object describing this M:M relationship]
     */
    static public $relationships = [];

    /*
     * @method void __construct( array $params )
     * $params		= Contains following elements (* = required):
     *	$modelSource*	= Name of Model on the LEFT side of the relationship
     *	$modelTarget*	= Name of Model on the RIGHT side of the relationship
     *	$cardinality*	= The cardinality of the relationship (see constants)
     *	$modelLink		= Name of Model that links source and target, if the cardinality is M:M
     *	$reference		= Custom reference for this relationship (defaults to self::REF_DEFAULT)
     *	$limit			= For 1:M relationships you can define a limit for M (for example, a 1:1 relation is just a 1:M with limit of 1)
     *	$nativeKey		= Name of the field used by the Model in the LEFT side of the relationship
     *	$foreignKey		= Name of the field used by the Model in the RIGHT side of teh relationship
     *	$options		= List of options to be set on this relationship (see below)
     *
     * Creates a new ModelRelation instance.
     * The possible options that can be set are:
     *	nocascade		= Prevents Models on the "M" side of a "1:M" relationship being deleted when the "1" side is deleted.
     *	nocascadedelete	= Prevents Models on the "M" side of a "1:M" relationship being deleted when the "1" side is deleted.
     *	nocascadesave	= Prevents Models on the "M" side of a "1:M" relationship being saved when the "1" side is saved.
     *
     * TODO: Remove support for "nocascade" and replace with "nocascadedelete" - need to change all existing applications that use this.
     */
    private function __construct($params)
    {
        $cardinality = null;
        $modelTarget = null;

        // Extract params TODO: Purge this to the depths of hell.
        extract($params);

        if (!isset($modelSource) || !isset($modelTarget) || !isset($cardinality)) {
            throw new Exception('Required parameters not defined for this Model Relationship.');
        }

        // Convert a "1:1" relationship to "1:M,1"
        if ($cardinality == ModelRelation::ONE_TO_ONE) {
            $cardinality = ModelRelation::ONE_TO_MANY;
            $limit = 1;
        }

        // Set attributes
        $this->modelSource = $modelSource;
        $this->modelTarget = $modelTarget;
        $this->modelLink = isset($modelLink) ? $modelLink : null;
        $this->reference = isset($reference) ? $reference : self::REF_DEFAULT;
        $this->cardinality = $cardinality;
        $this->limit = $cardinality == ModelRelation::ONE_TO_MANY ? (isset($limit) ? (int) $limit : null) : null;
        $this->nativeKey = !isset($nativeKey) ? ($cardinality == self::MANY_TO_ONE ? Inflector::modelName_dbTableName($modelTarget) . '_id' : 'id') : $nativeKey;
        $this->foreignKey = !isset($foreignKey) ? ($cardinality == self::ONE_TO_MANY ? Inflector::modelName_dbTableName($modelSource) . '_id' : 'id') : $foreignKey;
        $this->options = !isset($options) ? [] : (!is_array($options) ? explode(",", $options) : $options);

        // Determine if this is a recursive relationship
        $this->isRecursive = $this->modelSource == $this->modelTarget ? true : false;

        // Warn is a M:M relationship is missing a linking Model
        if ($this->cardinality == self::MANY_TO_MANY && $this->modelLink === null) {
            SystemLog::add('A M:M relationship is missing a linking Model in ModelRelation.', SystemLog::WARNING);
        }
    }

    /*
     * @method void define( string $relationship, [string $options, [string $relationRef, [string $manyToManyPartial]]] )
     * $relationship			= The relationship definition between 2 or more Models
     * $options				= A comma-separated list of options to set on this relationship
     * $relationRef			= String to identify the particulat relationship between source and target Models
     * $manyToManyPartial	= Name of the linking Model that joins source and target in a M:M relationship (INTERNAL USE ONLY)
     *
     * Parses the given definition into several ModelRelation objects which, when combined with
     * all other ModelRelations, starts to build a comprehensive relationship mapping.
     * $relationship is in the form:
     *		ModelA[.nativeKey](cardinalityA,[limitA]):ModelB[.foreignKey](cardinalityB,[limitB])
     * or	ModelA[.nativeKey](cardinalityA,[limitA]):ModelAB[.foreignKeyA][.foreignKeyB](cardinalityB,[limitB]):ModelB[.nativeKey](cardinalityB,[limitB])
     *
     * eg.	Blog(1):BlogEntry(M)
     * eg.	Book(1):BookAuthor(M):Author(1)						// here, "BookAuthor" is a Model that links two other Models, thus creating a M:M relationship
     * eg.	Book(M):Author(M)									// This is a shortcut for the preceding definition. The system will assume linking table is "BookAuthor"
     * eg.	Husband(1):Wife(1)
     * eg.	Husband(1):Wife(M,1)								// This is an alternative way of writing the preceding definition
     * eg.	Person.id(1):Hobby.person_id(M)						// This shows the optional ability to define custom native and foreign-keys in a relationship
     * eg.	Book(1):BookAuthor.book_id.author_id(M):Author(1)	// Note the double foreign-key in this special defintion type
     *
     * The $relationRef parameter is only required if you are defining the same relationship several times, but
     * on different table columns. For example:
     *		User(1):Bug.reporter_id(M)	- Could use, for example, a $relationRef of "reporter"
     *		User(1):Bug.assigned_id(M)	- Could use, for example, a $relationRef of "assigned"
     *
     * Below are listed the methods for defining different relationshsips ...
     * 1:M
     *		Blog(1):BlogEntry(M)
     *		Husband(1):Wife(1)
     *		Husband(1):Wife(M,1)	- Same as previous definition
     *
     *	M:M
     *		Book(1):BookAuthor(M):Author(1)	- BookAuthor is the "link model" between Books and Authors
     *
     * M:M recursive
     *		Person(1):Friend.fk1_id.fk2_id(M):Person(1)	- You MUST define the foreign
     *			keys used in the link model otherwise the system assumes they are both
     *			the same (person_id in this example) which is no help at all!
     */
    static public function define($relationship, $options = null, $relationRef = null, $manyToManyPartial = null)
    {

        // Keep track of relationships that have already previously been processed.
        // Recursion can occur when defining M:M relationships, so this will prevent system hang-ups.
        static $processedRelationships = [];
        if (in_array($relationship, $processedRelationships)) {
            return null;
        }
        $processedRelationships[] = $relationship;

        // Vars
        if ($relationRef === null) {
            $relationRef = self::REF_DEFAULT;
        }

        // If more than one relationship is being defined here (ie. 1:M:1), break it down into
        // single relationships and parse each one separately.
        // Also, the resulting M:M relationship is defined.
        if (substr_count($relationship, ":") > 1) {

            // Extract components
            $components = explode(":", $relationship);
            $mSource = preg_replace("/\(.+$/", "", $components[0]);
            $mLink = preg_replace("/\(.+$/", "", $components[1]);
            $mTarget = preg_replace("/\(.+$/", "", $components[2]);
            $mSourceKey = $mTargetKey = null;
            if (strpos($mSource, ".")) {
                list($mSource, $mSourceKey) = explode(".", $mSource);
            }
            if (strpos($mTarget, ".")) {
                list($mTarget, $mTargetKey) = explode(".", $mTarget);
            }
            $mLinkSourceKey = Inflector::modelName_dbTableName($mSource) . '_id';
            $mLinkTargetKey = Inflector::modelName_dbTableName($mTarget) . '_id';
            if (strpos($mSource, ".") !== false) {
                list($mSource, $mSourceKey) = explode(".", $mSource);
            }
            if (strpos($mTarget, ".") !== false) {
                list($mTarget, $mTargetKey) = explode(".", $mTarget);
            }
            if (strpos($mLink, ".") !== false) {
                list($mLink, $mLinkSourceKey, $mLinkTargetKey) = explode(".", $mLink);
            }

            // Define the resulting M:M relationship
            self::$relationships[$mSource][$mTarget][$relationRef] = new ModelRelation([
                'modelSource' => $mSource,
                'modelTarget' => $mTarget,
                'cardinality' => ModelRelation::MANY_TO_MANY,
                'modelLink' => $mLink,
                'nativeKey' => $mSourceKey,
                'foreignKey' => $mTargetKey
            ]);
            if ($mSource != $mTarget) {
                self::$relationships[$mTarget][$mSource][$relationRef] = new ModelRelation([
                    'modelSource' => $mTarget,
                    'modelTarget' => $mSource,
                    'cardinality' => ModelRelation::MANY_TO_MANY,
                    'modelLink' => $mLink,
                    'nativeKey' => $mSourceKey,
                    'foreignKey' => $mTargetKey
                ]);

                self::define("{$components[0]}:{$mLink}.{$mLinkSourceKey}(M)", $options, $relationRef, $mTarget);
                self::define("{$mLink}.{$mLinkSourceKey}(M):{$components[0]}", $options, $relationRef, $mTarget);
                self::define("{$mLink}.{$mLinkTargetKey}(M):{$components[2]}", $options, $relationRef, $mSource);
                self::define("{$components[2]}{$mLink}.{$mLinkTargetKey}(M)", $options, $relationRef, $mSource);
            } else {
                self::$relationships[$mSource][$mTarget][self::REF_CHILD] = new ModelRelation([
                    'modelSource' => $mSource,
                    'modelTarget' => $mTarget,
                    'cardinality' => ModelRelation::MANY_TO_MANY,
                    'modelLink' => $mLink,
                    'reference' => self::REF_CHILD,
                    'nativeKey' => $mSourceKey,
                    'foreignKey' => $mTargetKey
                ]);

                self::define("{$components[0]}:{$mLink}.{$mLinkSourceKey}(M)", $options, self::REF_PARENT);
                self::define("{$mLink}.{$mLinkTargetKey}(M):{$components[2]}", $options, self::REF_CHILD);
            }

            //// Define the individual relationships, ie. 1:M and M:1
            /*if($mSource!=$mTarget) {
                self::define("{$components[0]}:{$mLink}.{$mLinkSourceKey}(M)", $options, $relationRef, $mTarget);
                self::define("{$mLink}.{$mLinkSourceKey}(M):{$components[0]}", $options, $relationRef, $mTarget);
                self::define("{$mLink}.{$mLinkTargetKey}(M):{$components[2]}", $options, $relationRef, $mSource);
                self::define("{$components[2]}{$mLink}.{$mLinkTargetKey}(M)", $options, $relationRef, $mSource);
            }
            else {
                if($mLinkSourceKey==$mLinkTargetKey) {
                    $mLinkSourceKey = "src_{$mLinkSourceKey}";
                    $mLinkTargetKey = "tgt_{$mLinkTargetKey}";
                }
                //self::define("{$components[0]}:{$mLink}.{$mLinkSourceKey}(M)", $options, self::REF_PARENT);
                //self::define("{$mLink}.{$mLinkSourceKey}(M):{$components[0]}", $options, self::REF_PARENT);
                //self::define("{$mLink}.{$mLinkTargetKey}(M):{$components[2]}", $options, self::REF_CHILD);
                //self::define("{$components[2]}{$mLink}.{$mLinkTargetKey}(M)", $options, self::REF_CHILD);
            }*/

            // Result
            return null;
        }

        // Extract components of the relationship into an ordered array
        preg_match_all("/([^:]*?)\((.*?)\)/", $relationship, $m);
        // $m_all = $m[0];
        $m_model = $m[1];
        $m_card = $m[2];
        $m_key = [null, null];
        $limit = [null, null];
        if (strpos($m_model[0], ".") !== false) {
            list($m_model[0], $m_key[0]) = explode(".", $m_model[0]);
        }
        if (strpos($m_model[1], ".") !== false) {
            list($m_model[1], $m_key[1]) = explode(".", $m_model[1]);
        }

        // Create the ModelRelation instances
        if (!isset(self::$relationships[$m_model[0]][$m_model[1]][$relationRef])) {

            // Extract any limits imposed on the cardinality
            foreach ($m_card as $k => $v) {
                if (strpos($v, ",") !== false) {
                    $limit[$k] = (int) preg_replace("/^.*?,([0-9]+)/", "$1", $v);
                    $m_card[$k] = preg_replace("/,.*/", "", $v);
                }
            }

            // Find the cardinality between the two Models
            $cardinalityMap = [
                '1:M' => ModelRelation::ONE_TO_MANY,
                'M:1' => ModelRelation::MANY_TO_ONE,
                'M:M' => ModelRelation::MANY_TO_MANY,
                '1:1' => ModelRelation::ONE_TO_ONE
            ];
            $cardinality = $cardinalityMap[strtoupper(implode(":", $m_card))];
            $revCardinality = $cardinalityMap[strtoupper(implode(":", array_reverse($m_card)))];

            // If a M:M cardinality is defined, we need to insert a linking table with an assumed name,
            // and then define the required 1:M:1 relationship.
            // We will assume that no keys have been defined in this M:M relationship, because it doesn't
            // make sense as the Models are not directly related.
            //$linkModel = NULL;
            if ($cardinality == ModelRelation::MANY_TO_MANY) {
                $linkModel = $m_model[0] . $m_model[1];
                $relation = $m_model[0] . '(1):' . $linkModel . '(M):' . $m_model[1] . '(1)';
                self::define($relation, $options, $relationRef);
                return null;
            }

            // If a 1:1 relation is defined, change it to a 1:M,1
            if ($cardinality == self::ONE_TO_ONE) {
                $cardinality = self::ONE_TO_MANY;
                $revCardinality = self::MANY_TO_ONE;
                $limit = [1, 1];
            }

            // For recursive 1:M relationships, make sure that the definition is 1:M, not M:1
            if ($m_model[0] == $m_model[1] && $cardinality == self::MANY_TO_ONE) {
                $m_model = array_reverse($m_model);
                $cardinality = self::ONE_TO_MANY;
                $revCardinality = self::MANY_TO_ONE;
                $limit = array_reverse($limit);

                // Create the "M:1" relationship here (ref: self::REF_CHILD)
                self::$relationships[$m_model[1]][$m_model[0]][self::REF_CHILD] = new ModelRelation([
                    'modelSource' => $m_model[1],
                    'modelTarget' => $m_model[0],
                    'cardinality' => $revCardinality,
                    'modelLink' => $manyToManyPartial,
                    'reference' => self::REF_CHILD,
                    'limit' => $limit[0],
                    'nativeKey' => $m_key[1],
                    'foreignKey' => $m_key[0],
                    'options' => $options
                ]);
            } else {
                if ($m_model[0] == $m_model[1] && $cardinality == self::ONE_TO_MANY) {
                    // Create the "M:1" relationship here (ref: self::REF_CHILD)
                    self::$relationships[$m_model[1]][$m_model[0]][self::REF_CHILD] = new ModelRelation([
                        'modelSource' => $m_model[1],
                        'modelTarget' => $m_model[0],
                        'cardinality' => $revCardinality,
                        'modelLink' => $manyToManyPartial,
                        'reference' => self::REF_CHILD,
                        'limit' => $limit[0],
                        'nativeKey' => $m_key[1],
                        'foreignKey' => $m_key[0],
                        'options' => $options
                    ]);
                }
            }

            // Extract options
            $options = $options === null ? null : explode(",", strtolower($options));

            // Create relationships
            self::$relationships[$m_model[0]][$m_model[1]][$relationRef] = new ModelRelation([
                'modelSource' => $m_model[0],
                'modelTarget' => $m_model[1],
                'cardinality' => $cardinality,
                'modelLink' => $manyToManyPartial,
                'reference' => $relationRef,
                'limit' => $limit[1],
                'nativeKey' => $m_key[0],
                'foreignKey' => $m_key[1],
                'options' => $options
            ]);
            if ($m_model[0] != $m_model[1] && $manyToManyPartial === null) {
                self::$relationships[$m_model[1]][$m_model[0]][$relationRef] = new ModelRelation([
                    'modelSource' => $m_model[1],
                    'modelTarget' => $m_model[0],
                    'cardinality' => $revCardinality,
                    'modelLink' => $manyToManyPartial,
                    'reference' => $relationRef,
                    'limit' => $limit[0],
                    'nativeKey' => $m_key[1],
                    'foreignKey' => $m_key[0],
                    'options' => $options
                ]);
            }
        }
    }

    /*
     * Finds and returns a ModelRelation that describes the relationship between the two given Models (or all other Models if
     * $modelTarget is not defined).
     *
     * If you do not specify $relationRef and multiple relationships are available for a particular source/target Model pair,
     * then ALL relations will be returned as an array hash (index by relation reference). If neither $modelTarget or $relationRef
     * are specified then all relations will be returned in a 1-dimensional array (no reference indexes).
     *
     * You can enter two Models that are part of a M:M relationship and this function will attempt to find a Model that links those Models.
     * If none is found then it returns a ModelRelation instance with a cardinality of NONE.
     * @param string $modelSource Name of the Model on the LEFT side of the relationship
     * @param string $modelTarget Name of the Model on the RIGHT side of the relationship
     * @param string $relationRef The specific relationship to return
     * @return ModelRelation[]
      */
    static public function getRelation($modelSource, $modelTarget = null, $relationRef = null)
    {

        // Pass back ALL relationships
        if ($modelTarget === null) {
            $relations = [];
            $relationMap = isset(self::$relationships[$modelSource]) ? self::$relationships[$modelSource] : [];
            foreach ($relationMap as $k => $rel) {
                foreach ($rel as $r) {
                    $relations[] = $r;
                }
                reset(self::$relationships[$modelSource][$k]);
            }
            return $relations;
        }

        // First, check if we've got a direct relationship (works for 1:1, 1:M and M:1)
        if (isset(self::$relationships[$modelSource][$modelTarget])) {
            if ($relationRef === null) {
                $rel = count(self::$relationships[$modelSource][$modelTarget]) > 1 ? self::$relationships[$modelSource][$modelTarget] : current(self::$relationships[$modelSource][$modelTarget]);
                return $rel;
            } else {
                if (isset(self::$relationships[$modelSource][$modelTarget][$relationRef])) {
                    return self::$relationships[$modelSource][$modelTarget][$relationRef];
                } else {
                    if ($modelSource != $modelTarget) {
                        SystemLog::add("No relationship with reference '{$relationRef}' has been defined for {$modelSource}:{$modelTarget}", SystemLog::CORE);
                        return new ModelRelation([
                            'modelSource' => $modelSource,
                            'modelTarget' => $modelTarget,
                            'cardinality' => ModelRelation::NONE
                        ]);
                    }
                }
            }
        }

        // Try to construct a M:M relationship
        /*if(isset(self::$relationships[$modelSource])) {
            $relationRef = $relationRef===NULL ? self::REF_DEFAULT : $relationRef;
            foreach(self::$relationships[$modelSource] as $linkModel=>$relation) {
                if(isset(self::$relationships[$linkModel][$modelTarget][$relationRef])) {
                    return self::$relationships[$modelSource][$modelTarget][$relationRef] = new ModelRelation(array(
                            'modelSource'=>$modelSource,
                            'modelTarget'=>$modelTarget,
                            'cardinality'=>ModelRelation::MANY_TO_MANY,
                            'modelLink'=>$linkModel,
                            'reference'=>$relationRef
                        ));
                }
            }
        }*/

        // If all else fails, return a ModelRelation with NONE cardinality
        SystemLog::add("No relationship has been defined for {$modelSource}:{$modelTarget}", SystemLog::CORE);
        return new ModelRelation([
            'modelSource' => $modelSource,
            'modelTarget' => $modelTarget,
            'cardinality' => ModelRelation::NONE
        ]);
    }

    static public function getRelationsByCardinality($modelSource, $cardinality)
    {
        $relations = [];
        $relationMap = isset(self::$relationships[$modelSource]) ? self::$relationships[$modelSource] : [];
        /**
         * @var string $modelTarget
         * @var ModelRelation[] $targetRelations
          */
        foreach ($relationMap as $modelTarget => $targetRelations) {
            foreach ($targetRelations as $ref => $rel) {
                if ($rel->getCardinality() == $cardinality) {
                    $relations[] = $rel;
                }
            }
        }
        return $relations;
    }

    /*
     * @method string getSourceModel()
     *
     * Returns $this->modelSource
     */
    public function getSourceModel()
    {

        // Result
        return $this->modelSource;
    }

    /*
     * @method string getTargetModel()
     *
     * Returns $this->modelTarget
     */
    public function getTargetModel()
    {

        // Result
        return $this->modelTarget;
    }

    /*
     * @method string getLinkModel()
     *
     * Returns $this->modelLink
     */
    public function getLinkModel()
    {

        // Result
        return $this->modelLink;
    }

    /*
     * @method string getReference()
     *
     * Returns $this->reference
     */
    public function getReference()
    {

        // Result
        return $this->reference;
    }

    /*
     * @method int getCardinality()
     *
     * Returns $this->cardinality
     */
    public function getCardinality()
    {

        // Result
        return $this->cardinality;
    }

    /*
     * @method int getLimit()
     *
     * Returns $this->limit
     */
    public function getLimit()
    {

        // Result
        return $this->limit;
    }

    public function getNativeKey()
    {
        return $this->nativeKey;
    }

    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /*
     * @method bool getOption( string $option )
     * $option	= Name of the option to find
     *
     * Returns TRUE if the specified option has been set, FALSE otherwise.
     */
    public function getOption($option)
    {

        // Result
        return in_array($option, $this->options);
    }

    /*
     * @method bool isRecursive()
     *
     * Returns $this->isRecursive
     */
    public function isRecursive()
    {

        // Result
        return $this->isRecursive;
    }

    public function isManyToManyPartial()
    {
        return !$this->isManyToMany() && $this->getLinkModel() !== null;
    }

    /*
     * @method bool isOneToOne()
     *
     * Returns TRUE if this is a 1:1 relationship.
     */
    public function isOneToOne()
    {

        // Result
        return $this->cardinality == self::ONE_TO_ONE;
    }

    /*
     * @method bool isOneToMany()
     *
     * Returns TRUE if this is a 1:M relationship.
     */
    public function isOneToMany()
    {

        // Result
        return $this->cardinality == self::ONE_TO_MANY;
    }

    /*
     * @method bool isManyToOne()
     *
     * Returns TRUE if this is a M:1 relationship.
     */
    public function isManyToOne()
    {

        // Result
        return $this->cardinality == self::MANY_TO_ONE;
    }

    /*
     * @method bool isManyToMany()
     *
     * Returns TRUE if this is a M:M relationship.
     */
    public function isManyToMany()
    {

        // Result
        return $this->cardinality == self::MANY_TO_MANY;
    }

    public function isNone()
    {
        return $this->cardinality === self::NONE;
    }

    public function getManyToManyRelation()
    {

        return $this->getLinkModel() === null ? new ModelRelation([]) : ModelRelation::getRelation($this->isOneToMany() ? $this->modelSource : $this->modelTarget, $this->modelLink);
    }

    /*
     * @method ModelRelation getInverseRelation()
     *
     * Returns a ModelRelation object that describes the inverse relationship of $this.
     */
    public function getInverseRelation()
    {

        // Get attributes to be duplicated
        $modelSource = $this->getSourceModel();
        $modelTarget = $this->getTargetModel();
        $cardinality = $this->getCardinality() == self::ONE_TO_MANY ? self::MANY_TO_ONE : ($this->getCardinality() == self::MANY_TO_ONE ? self::ONE_TO_MANY : $this->getCardinality());
        $modelLink = $this->getLinkModel();
        $limit = $this->getLimit();

        // For recursive relationships we need to manually reverse the cardinality
        if ($this->isRecursive()) {

            // Determine the inverse relation reference
            $invRelationRef = self::REF_DEFAULT;
            foreach (self::$relationships[$modelSource][$modelTarget] as $ref => $rel) {
                if ($ref != $this->getReference()) {
                    $invRelationRef = $ref;
                }
            }

            // Result
            return new ModelRelation([
                'modelSource' => $modelTarget,
                'modelTarget' => $modelSource,
                'cardinality' => $cardinality,
                'modelLink' => $modelLink,
                'reference' => $invRelationRef,
                'limit' => $limit
            ]);
        } // Pre-defined relationships
        else {
            if (isset(self::$relationships[$modelTarget][$modelSource])) {
                $invRelation = self::$relationships[$modelTarget][$modelSource];
                if (count($invRelation) > 1) {
                    foreach ($invRelation as $ref => $rel) {
                        if ($ref != $this->getReference()) {
                            return $rel;
                        }
                    }
                } else {
                    return $invRelation[self::REF_DEFAULT];
                }
            } // No inverse relationship found
            else {
                return new ModelRelation([
                    'modelSource' => $modelTarget,
                    'modelTarget' => $modelSource,
                    'cardinality' => self::NONE
                ]);
            }
        }
        return null;
    }

    /**
     * Un-defines a relationship.
     *
     * @param string
     * @param string
     * @param string
     * @return void
      */
    public static function undefine($modelSource, $modelTarget, $relationRef)
    {
        if (isset(self::$relationships[$modelSource][$modelTarget][$relationRef])) {
            unset(self::$relationships[$modelSource][$modelTarget][$relationRef]);
        }
    }
}

?>