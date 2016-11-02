<?php
/**
 * @package Buan
 */
namespace Buan;

use \PDO;
use \PDOException;

class ModelManager
{

    /**
     * Stores the name of the Model operated upon by this ModelManager.
     *
     * @var string
     */
    public $modelName;

    /**
     * Creates an instance of the model-specific manager.
     *
     * Do not call this directly from your scripts, instead use:
     * ModelManager::create()
     *
     * @param string $modelName Name of the Model for which this is the ModelManager
     */
    public function __construct($modelName)
    {
        // Store Model name
        $this->modelName = $modelName;
    }

    /**
     * Factory method that returns a Singleton instance of the ModelManager class
     * that is used for performing CRUD actions on Models of the type specified.
     *
     * @param string $modelName The name of the Model whose manager we want to retrieve
     * @return \Buan\ModelManager
     */
    final static public function create($modelName)
    {

        // Vars
        static $modelManagerInstances = [];

        // Return instance if already created
        if (isset($modelManagerInstances[$modelName])) {
            return $modelManagerInstances[$modelName];
        }

        // Create new instance and return
        $managerClassName = Inflector::modelName_modelManagerClass($modelName);
        try {
            $modelManagerInstances[$modelName] = new $managerClassName($modelName);
        } catch (Exception $e) {
            SystemLog::add($e->getMessage(), SystemLog::CORE);
            $modelManagerInstances[$modelName] = new ModelManager($modelName);
        }
        return $modelManagerInstances[$modelName];
    }

    /**
     * Removes the specified Model form the database.
     *
     * Returns TRUE on successful removal, FALSE otherwise.
     *
     * @todo There's question of recursion happening. Suck it and see.
     *
     * @param Model $model Model to be deleted
     * @return bool
     */
    public function delete(Model $model)
    {

        // Check if Model is actually in the database
        // (can't delete Models that are not in persistent storage)
        if (!$model->isInDatabase()) {
            SystemLog::add("Attempting to delete non-persistent Model ({$model->modelName} #{$model->getPrimaryKeyValue()}).", SystemLog::CORE);
            return true;
        }

        // Get a DB connection for the given Model and start a transaction.
        $DB = null;
        try {
            $DB = Database::getConnectionByModel($model);
            // $dbTransactionActive = $DB->getAttribute(PDO::ATTR_AUTOCOMMIT) == 1 ? true : false;
            $DB->beginTransaction();
            $dbTransactionActive = true;
        } catch (Exception $e) {
            SystemLog::add($e->getMessage(), SystemLog::NOTICE);
            $dbTransactionActive = false;
        }

        // Delete
        $original_isInDatabase = $model->isInDatabase();
        $original_hasChanged = $model->hasChanged();
        try {
            // Delete the model itself from the db
            $sql = 'DELETE FROM `' . $model->getDbTableName() . '` WHERE ' . $model->getPrimaryKey() . '=?';
            $stmt = $DB->prepare($sql);
            $stmt->execute([$model->getPrimaryKeyValue()]);

            // Find all relationships that include $model
            $relations = ModelRelation::getRelation($model->modelName);
            foreach ($relations as $relation) {
                switch ($relation->getCardinality()) {

                    // 1:M (including 1:1, ie. 1:M,1)
                    case ModelRelation::ONE_TO_MANY:

                        // Delete all "M" Models, if cascading is allowed
                        $relatedModels = $model->findRelatives($relation->getTargetModel(), $relation->getReference());
                        if ($relation->getLimit() == 1) {
                            $relatedModels = $relatedModels->isEmpty() ? [] : $relatedModels->asArray();
                        }
                        if (!$relation->getOption('nocascade')) {
                            foreach ($relatedModels as $rModel) {
                                $model->disownRelatives($rModel);
                                if (!$rModel->getModelManager()->delete($rModel)) {
                                    // TODO: Do we rollback here?
                                }
                                //$model->disownRelatives($rModel);
                            }
                        }

                        // Set the foreign key on all "M" Models to 0 (zero), if
                        // cascading is prohibited.
                        // Once the foreign-key is set, the related Model is then saved.
                        // TODO: Setting the field to "0" doesn't help if the
                        // foreign key is not an integer! Need to support strings or
                        // just use NULL?
                        else {
                            foreach ($relatedModels as $rModel) {
                                $model->disownRelatives($rModel);
                                $rModel->{$rModel->getForeignKey($model, $relation->getReference())} = 0;
                                if (!$rModel->getModelManager()->save($rModel)) {
                                    // TODO: Rollback?
                                }
                            }
                        }

                        // Break
                        break;

                    // M:1
                    case ModelRelation::MANY_TO_ONE:

                        // Break relation between Models
                        //$relatedModel = $model->findRelatives($relation->getTargetModel(), $relation->getReference())->get(0);
                        $relatedModel = $model->findRelatives($relation->getTargetModel(), $relation->getReference());
                        //if($relatedModel!==NULL) {
                        if (!$relatedModel->isEmpty()) {
                            //$model->disownRelatives($relatedModel);
                            $model->disownRelatives($relatedModel);
                        }
                        break;

                    // M:M
                    case ModelRelation::MANY_TO_MANY:

                        // M:M relationships should actually already be broken down into 1:M/M:1
                        // so this case can be ignored.
                        break;

                    // default
                    default:
                        break;
                }
            }

            // Return
            if ($dbTransactionActive) {
                $DB->commit();
            }

            // Set flags on $model to indicate that it's no longer persistent
            $model->isInDatabase(false);
            $model->hasChanged(false);

            // Result
            return true;
        } catch (PDOException $e) {

            // Set attributes
            $model->isInDatabase($original_isInDatabase);
            $model->hasChanged($original_hasChanged);

            // Log, rollback and return
            SystemLog::add($e->getMessage(), SystemLog::WARNING);
            try {
                if ($dbTransactionActive) {
                    $DB->rollBack();
                }
            } catch (PDOException $e) {
                SystemLog::add($e->getMessage(), SystemLog::WARNING);
            }
            return false;
        }
    }

    /**
     * Load all field data into $model, using $model's primary-key on which to
     * retrieve data from the database.
     *
     * Returns TRUE on a successful load, FALSE otherwise. Throws an Exception if
     * anything unexpected happens.
     *
     * @param Model $model This instance will be populated with content from the db
     * @return bool
     * @throws Exception
     */
    public function load(Model $model)
    {

        // If no fields have been set/altered, then don't bother reloading from db
        if (!$model->hasChanged()) {
            return true;
        }

        // Find and load Model from the database
        try {
            // Prepare and execute the query for loading this Model from the database
            $DB = Database::getConnectionByModel($model);
            $primaryKeys = explode(",", $model->getPrimaryKey());
            $primaryKeyValues = $model->getPrimaryKeyValue();
            $dbTableName = $model->getDbTableName();
            $sql = 'SELECT * FROM `' . $dbTableName . '` WHERE ' . implode("=? AND ", $primaryKeys) . '=?';
            $stmt = $DB->prepare($sql);
            $stmt->execute(is_array($primaryKeyValues) ? array_values($primaryKeyValues) : [$primaryKeyValues]);

            // When a single record is found, populate the Model
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($records) == 1) {

                // Populate
                $model->populateFromArray($records[0]);
                $model->isInDatabase(true);
                $model->hasChanged(false);

                // Result
                return true;
            } // No matching records found?
            else {
                if (count($records) == 0) {
                    return false;
                }

                // If more than one record was found then we've got issues with the PK
                // being not-so-primary!
                else {
                    $fnDump = implode(", ", array_keys($model->getDbData()));
                    $fvDump = "'" . implode("', '", array_values($model->getDbData())) . "'";
                    SystemLog::add("Found multiple Models on primary key (type:{$model->modelName}, table:{$model->getDbTableName()}, data:[{$fnDump}] => [{$fvDump}]", SystemLog::WARNING);
                    return false;
                }
            }
        }

            // Handle any other problems by passing the Exception back to the caller for
            // handling
        catch (Exception $e) {
            SystemLog::add($e->getMessage(), SystemLog::WARNING);
            throw new Exception($e->getMessage());
            return false;
        }
    }


    /**
     * Refresh the Model by reloading it's field data from the database.
     *
     * @param \Buan\Model Model to be re-loaded.
     * @return bool
     */
    public function refresh($model)
    {

        // Reload
        return $this->load($model);
    }

    /**
     * Saves the given Model to the database.
     *
     * If the Model already exists in the database, an UPDATE will issued instead.
     *
     * Returns TRUE on a successful save, FALSE otherwise.
     *
     * @param \Buan\Model Model to save
     * @param bool
     * @return bool
     */
    public function save($model)
    {

        // Get a DB connection for the given Model and start a transaction.
        // MySQL: Tables must use "innodb" engine for transactions to work.
        try {
            $DB = Database::getConnectionByModel($model);
            $DB->beginTransaction();
        } catch (Exception $e) {
            SystemLog::add($e->getMessage(), SystemLog::NOTICE);
        }

        // M:1
        // We first need to save any foreign Models in order to populate any foreign
        // keys in $model with valid, persistent values.
        $cd = ModelRelation::MANY_TO_ONE;
        if (isset($model->relatives[$cd])) {
            foreach ($model->relatives[$cd] as $tm => $relation) {
                foreach ($relation as $rr => $relative) {
                    if (!$relative->hasChanged()) {
                        // Don't save, it's already in peristent storage
                    } else {
                        if ($relative->getModelManager()->save($relative)) {
                            $model->setForeignKeyValue($relative->modelName, $relative->getPrimaryKeyValue(), $rr);
                        } else {
                            $DB->rollback();
                            return false;
                        }
                    }
                }
            }
        }

        // Save/Update $model
        try {
            // Issue the SQL insert/update query only if the $model has changed. If
            // updating then we need to first unset all PK's to avoid SQL errors.
            $inUpdateMode = false;
            if ($model->isInDatabase()) {
                $inUpdateMode = true;
            }
            if ($model->hasChanged()) {
                $dbData = $model->getDbData();
                $dbTableName = $model->getDbTableName();
                if (!$inUpdateMode) {
                    $sql = 'INSERT INTO `' . $dbTableName . '` (' . implode(",", array_keys($dbData)) . ')
						VALUES (' . implode(",", array_fill(0, count($dbData), '?')) . ')';
                } else {
                    $pk = $model->getPrimaryKey();
                    $pkv = [];
                    if (is_array($pk)) {
                        foreach ($pk as $k) {
                            $pkv[$k] = $dbData[$k];
                            unset($dbData[$k]);
                        }
                    } else {
                        if ($pk !== null) {
                            $pkv[$pk] = $dbData[$pk];
                            unset($dbData[$pk]);
                            $pk = [$pk];
                        }
                    }
                    $sql = 'UPDATE `' . $dbTableName . '` SET ' . implode("=?, ", array_keys($dbData)) . '=?
									WHERE ' . implode("=?, ", $pk) . '=?';

                    // Add the PK values back to the end of the dbData array so they can
                    // be included in the prepared statement. It's important they go in
                    // the correct order at the end so they correspond to the ? stmt
                    // placeholders correctly
                    foreach ($pkv as $k => $v) {
                        $dbData[$k] = $v;
                    }
                }

                // Prepare and execute
                $stmt = $DB->prepare($sql);
                if (!$stmt->execute(array_values($dbData))) {
                    SystemLog::add("PDOStatement execution failed.", SystemLog::WARNING);
                    return false;
                }

                // If saving for the first time, and the primary key is an
                // "auto_increment" field then store the newly generated ID in
                // this Model's primary key.
                // TODO: But what if we're not using an auto-inc field?? Or composite
                // keys?? Really we need to find the last record inserted and re-extract
                // data from it.
                if (!$inUpdateMode && ($lastInsertId = $DB->lastInsertId()) > 0) {
                    $model->setPrimaryKeyValue($lastInsertId);
                }
            }

            // Set flags to tell the system that $model is now persistent and
            // unchanged.
            $model->isInDatabase(true);
            $model->hasChanged(false);

            // 1:M
            // Save any in-memory models attached to $model
            $cd = ModelRelation::ONE_TO_MANY;
            if (isset($model->relatives[$cd])) {
                foreach ($model->relatives[$cd] as $tm => $relation) {
                    foreach ($relation as $rr => $relatives) {
                        foreach ($relatives as $relative) {
                            if ($relative->hasChanged()) {
                                $relative->setForeignKeyValue($model->modelName, $model->getPrimarykeyValue(), $rr);
                                if (!$relative->getModelManager()->save($relative)) {
                                    $DB->rollback();
                                    return false;
                                }
                            }
                        }
                    }
                }
            }

            // Commit and return
            $DB->commit();
            return true;
        }

            // If anything failed during the save/update then attempt to rollback and
            // reset flags on $model
        catch (PDOException $e) {

            // Reset flags
            $model->isInDatabase($inUpdateMode ? true : false);
            $model->hasChanged(true);

            // Log, rollback and return
            SystemLog::add($e->getMessage(), SystemLog::WARNING);
            try {
                $DB->rollBack();
            } catch (PDOException $e) {
                SystemLog::add($e->getMessage(), SystemLog::WARNING);
            }

            // Log it too, please.
            error_log($e->getMessage());

            return false;
        }
    }

    /**
     * Returns an array of Models of the specified type, according to any given criteria.
     *
     * @param string Name of the Model type to be selected
     * @param \Buan\ModelCriteria Filter resluts on this criteria
     * @return \Buan\ModelCollection
     * @throws ModelException
     */
    static public function select($modelName, $criteria = null)
    {

        // Create an instance of the Model type and it's Manager class
        $model = Model::create($modelName);
        $manager = ModelManager::create($modelName);

        // Process
        $records = [];
        try {
            // Get the DB connection used by Models of this type
            $DB = Database::getConnectionByModel($model);

            // Build, prepare and execute query
            if ($criteria === null) {
                $c = new ModelCriteria();
            } else {
                $c = clone $criteria;
            }
            $c->selectField("`{$model->getDbTableName()}`.*");
            $c->selectTable($model->getDbTableName());
            $sql = $c->sql();
            $stmt = $DB->prepare($sql->query);
            foreach ($sql->bindings as $binding) {
                $stmt->bindValue($binding->parameter, $binding->value, $binding->dataType);
            }
            $stmt->execute();

            // Create a new collection stream from the result set and return
            return new ModelCollection($modelName, $stmt);
        } // Pass Exceptions back to caller for custom handling
        catch (PDOException $e) {
            throw new ModelException("PDO Exception: {$e->getMessage()}");
        } catch (Exception $e) {
            throw new ModelException($e->getMessage());
        }
    }

    /**
     * Returns a count of all records of the specified type that match the given
     * criteria (if specified)
     *
     * @todo When PDOStatement->rowCount() is supported by all database drivers,
     * use it instead of the fetchAll() solution.
     *
     * @param string Name of the Model type to be selected
     * @param \Buan\ModelCriteria Filter by this criteria
     * @return int
     */
    static public function selectCount($modelName, $criteria = null)
    {

        // Create an instance of the Model type and it's Manager class
        $model = Model::create($modelName);

        // Get the DB connection used by Models of this type
        try {
            $DB = Database::getConnectionByModel($model);
        } catch (Exception $e) {
            SystemLog::add($e->getMessage(), SystemLog::WARNING);
            return 0;
        }

        // Build query criteria
        if ($criteria === null) {
            $c = new ModelCriteria();
        } else {
            $c = clone $criteria;
            //$c->ungroupBy();
            //if($c->hasSelectFields()) {
            //	$c->groupBy("NULL");
            //}
        }
        $c->selectTable($model->getDbTableName());

        // If no fields have been specified in the SELECT portion of the query,
        // then we'll use COUNT(*)
        //
        // The method of counting the rows is one of:
        //	FETCH_ALL	= The query is run as-is and rows are counted from the
        //				result of $stmt->fetchAll()
        //	COUNT_SQL	= The COUNT(*) method is used (for simple queries that
        //				do not already contain and FIELDS in the SELECT portion)
        $countMethod = 'FETCH_ALL';
        if (!$c->hasSelectFields()) {
            $c->selectField("COUNT(*) AS numRecords");
            $countMethod = 'COUNT_SQL';
        }

        // Prepare and execute query
        $sql = $c->sql();
        if (!$stmt = $DB->prepare($sql->query)) {

            // Log and return
            SystemLog::add('Failed to prepare PDO statement: ' . $sql, SystemLog::WARNING);
            return 0;
        } else {
            foreach ($sql->bindings as $binding) {
                $stmt->bindValue($binding->parameter, $binding->value, $binding->dataType);
            }
            if (!$stmt->execute()) {

                // Log and return
                SystemLog::add('Query failed: ' . $stmt->queryString, SystemLog::WARNING);
                return 0;
            }
        }

        // Get the count
        $rec = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rec) && $countMethod == 'COUNT_SQL') {
            SystemLog::add('ModelManager::selectCount() has not managed to retrieve any count.', SystemLog::WARNING);
            return 0;
        }
        return $countMethod == 'FETCH_ALL' ? count($rec) : (int) $rec[0]['numRecords'];
    }

    /**
     * This method allows you to execute any arbitrary SQL statement and the
     * results are returned as a PDOStatement, or FALSE if the query failed.
     *
     * If you want to use numeric parameters (ie. SELECT * FROM x WHERE y=?)
     * then pass $params as a normal 0-indexed array.
     * However, if you want to use named parameters
     * (ie. SELECT * FROM x WHERE y=:myparam), then send $params as a hash
     * key=>value pairs of ":param"=>"value".
     *
     * Really, you could just as easily use the PDO functions directly in your
     * code. This will give you more flexibilty with setting attributes, etc.
     * Just try to keep all database code within your Model or ModelManager
     * classes.
     *
     * @param string|\Buan\ModelCriteria The query to execute
     * @param array Parameters to bind to the query
     * @param string The DB connection through which the query will be executed
     * @return \PDOStatement
     * @throws \PDOException
     */
    static public function sqlQuery($sql, $params = [], $connection = null)
    {

        // Get the database connection
        if (is_null($connection)) {
            try {
                $connection = Database::getConnection('default');
            } catch (Exception $e) {
                SystemLog::add($e->getMessage(), SystemLog::WARNING);
                return false;
            }
        }

        // Execute the query
        try {
            if ($sql instanceof ModelCriteria) {
                $sql = $sql->sql();
                $stmt = $connection->prepare($sql->query);
                foreach ($sql->bindings as $binding) {
                    $stmt->bindValue($binding->parameter, $binding->value, $binding->dataType);
                }
                $stmt->execute();
            } else {
                if (count($params) > 0) {
                    $stmt = $connection->prepare($sql);
                    $stmt->execute($params);
                } else {
                    $stmt = $connection->query($sql);
                }
            }
            return $stmt;
        }

            // On failure, append some debugging information and pass back to caller for
            // handling
        catch (PDOException $e) {
            $dbg = debug_backtrace();
            $msg = $e->getMessage() . " (source: {$dbg[0]['file']} line {$dbg[0]['line']})";
            throw new PDOException($msg);
            return false;
        }
    }

    /**
     * Updates an existing Model.
     *
     * @param \Buan\Model Model to be updated
     * @param bool
     */
    public function update($model)
    {

        // If nothing has changed in the $model since it was first loaded, then
        // don't issue any updates
        if (!$model->hasChanged()) {
            return true;
        }

        // Pass through to $this->save()
        return $this->save($model);
    }
}

?>