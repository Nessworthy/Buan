<?php
/**
 * This class provides a means to encapsulate an SQL query within an object.
 *
 * @package Buan
 */
namespace Buan;
use \StdClass;

class ModelCriteria
{

	/*
	# @constant-group Operators
	# These constants represent the operators in an equation.
	*/
	const EQUALS = 1;
	const NOT_EQUALS = 2;
	const GREATER_THAN = 4;
	const GREATER_THAN_OR_EQUAL = 8;
	const LESS_THAN = 16;
	const LESS_THAN_OR_EQUAL = 32;
	const LIKE = 64;
	const NOT_LIKE = 128;
	const IS_NULL = 256;
	const IS_NOT_NULL = 512;
	const FIND_IN_SET = 1024;
	const IN = 2048;
	const NOT_IN = 4096;
	const BETWEEN = 8192;

	/*
	# @constant-group Logic strings
	# Logic strings.
	*/
	const LOGIC_OR = "OR";
	const LOGIC_AND = "AND";

	/*
	# @constant-group Others
	*/
	const WHERE = "WHERE";
	const HAVING = "HAVING";

	/*
	# @property array $selects
	# List of fields and tables used in the SELECT portion of the query.
	# If neither any fields or any tables exist then SELECT will not be included
	# in the rendered SQL, only the remaining criteria elements.
	*/
	private $selects = ['fields' => [], 'tables' => []];

	/*
	# @property ModelCriteriaGroup $whereGroup
	# This is the root clause group of which all other clause groups are
	# children.
	*/
	private $whereGroup = null;

	/*
	# @property array $leftJoins
	# Holds all LEFT JOINs. The order they appear in this array is the order
	# in which they are added to the generated SQL query.
	#
	# Format:
	#	$leftJoins = array(
	#		array(
	#			'table'=>[table name],
	#			'clause'=>[joining clause (ie. part used in "... ON ...")]
	#		),
	#		...
	#	);
	*/
	private $leftJoins = [];

	/*
	# @property array $rightJoins
	# Holds all RIGHT JOINs. The order they appear in this array is the order
	# in which they are added to the generated SQL query.
	#
	# Format:
	#	$rightJoins = array(
	#		array(
	#			'table'=>[table name],
	#			'clause'=>[joining clause (ie. part used in "... ON ...")]
	#		),
	#		...
	#	);
	*/
	private $rightJoins = [];

	/*
	# @property array $groupBys
	# List of fields that will be added to a GROUP BY clause.
	#
	# Format:
	#	$groupBys = array('[field-name]', ...);
	*/
	private $groupBys = [];

	/*
	# @property array $havingGroup
	# A ModelCriteriaGroup object that contains sub-groups that will be rendered
	# in the HAVING portion of the query, rather than the WHERE portion.
	*/
	private $havingGroup = [];

	/*
	# @property array $orders
	# Holds any ORDER clauses. The order they appear in this array is the order
	# in which they are added to the generated SQL query.
	#
	# Format:
	#	$orders = array(
	#		array(
	#			'fieldName'=>[ordering field],
	#			'direction'=>[order direction (ASC | DESC)]
	#		),
	#		...
	#	);
	*/
	private $orders = [];

	/*
	# @property array $limit
	# Holds the LIMIT clause criteria.
	#
	# Format:
	#	$limit = array(
	#		'start'=>[record number to start the limited range from],
	#		'recordCount'=>[max number of records to return]
	#	);
	*/
	private $limit = null;

	/*
	 # @property string $useIndex
	 # Contains the USE INDEX clause index list.
	 # 
	*/
	private $useIndex = [];

	/*
	 # @property bool $value
	 # If true, disables database cache; for perf. testing
	 # only.
	 #
	*/
	private $disableCache = false;

	/*
	 # @property array $aggregateSubqueries
	 # Contains actual subquery instances
	 #
	*/
	private $aggregateSubqueries = [];

	/*
	# @method void __construct()
	*/
	public function __construct()
	{

		// Create the root clause group
		$this->whereGroup = new ModelCriteriaGroup();
		$this->havingGroup = new ModelCriteriaGroup();
	}

	/*
	# @method ModelCriteriaGroup addGroup( [string $logic] )
	# $logic	= Evaluation logic used to join clauses in this new group
	#
	# Generate and return a clause group.
	*/
	public function addGroup($logic = ModelCriteria::LOGIC_AND, $type = ModelCriteria::WHERE)
	{

		// Generate and return
		return $type == self::WHERE ? $this->whereGroup->addGroup($logic) : ($type == self::HAVING ? $this->havingGroup->addGroup($logic) : null);
	}

	/**
	 * Runs $this criteria over the given Models and returns a ModelCollection
	 * containing all that satisfied the criteria.
	 *
	 * This only works for very simple criteria and clauses in the WHERE group.
	 *
	 * @todo Whilst the clause stuff is handled by ModelCriteriaGroup::applyTo(),
	 * the ordering and range criteria will have to be handled here.
	 *
	 * @param Model|ModelCollection Models to which $this criteria will be applied
	 * @return ModelCollection
	 */
	public function applyTo($models)
	{

		// Apply criteria to elements in the WHERE group
		$matches = $this->whereGroup->applyTo($models)->asArray();

		// Apply ordering
		foreach ($this->orders as $order) {
			usort($matches, function ($a, $b) use ($order) {
				$av = $a->{$order['fieldName']};
				$bv = $b->{$order['fieldName']};
				$result = $av > $bv ? 1 : ($av < $bv ? -1 : 0);
				return $order['direction'] == 'desc' ? -1 * $result : $result;
			});
		}

		// Apply range
		// TODO

		// Result
		return new ModelCollection($matches);
	}

	/**
	 * Returns TRUE if any SELECT fields have been defined.
	 *
	 * @return bool
	 */
	public function hasSelectFields()
	{
		return empty($this->selects['fields']) ? false : true;
	}

	/*
	# @method void selectField( string|ModelCriteria $field, [string $alias] )
	# $field	= Field (eg. "id", "*", "COUNT(*) AS c", etc) or subquery
	# $alias	= Field alias
	#
	# Add a field to the SELECT portion of the query.
	# If JOINing multiple tables in this query, then it's a good idea to prefix
	# the field with a table name.
	# You can actual insert a subquery by specifying $field as a ModelCriteria
	# object.
	*/
	public function selectField($field, $alias = null)
	{
		$fObj = new \stdClass();
		$fObj->alias = $alias;
		if ($field instanceof ModelCriteria) {
			$sql = $field->sql();
			$fObj->query = "({$sql->query})";
			$fObj->bindings = $sql->bindings;
		} else {
			$fObj->query = $field;
			$fObj->bindings = null;
		}
		$this->selects['fields'][] = $fObj;
	}

	/*
	 * Add an instance of AggregateSubquery
	 *
	 */
	public function addAggregateSubQuery($subquery)
	{
		$this->aggregateSubqueries[] = $subquery;
	}


	/*
	# @method void selectTable( string $table )
	# $table	= Table name
	#
	# Add a table to the FROM portion of the query.
	# Duplicates will be ignored.
	*/
	public function selectTable($table, $alias = null)
	{

		// Handle ModelCriteria - ie a subquery
		if ($table instanceof ModelCriteria) {
			$tSql = $table->sql();
			$this->selects['tables'][] = (object) [
				'table' => "({$tSql->query})",
				'alias' => $alias,
				'bindings' => $tSql->bindings
			];
			return;
		}

		// Wrap a simple table name in ` marks.
		// As $table can be, for example, "tablename AS tb", then we need to
		// ignore such cases and put to onus onto the user.
		if (!preg_match("/[^0-9a-z_]/i", $table)) {
			$table = substr($table, 0, 1) != "`" ? "`{$table}`" : $table;
		}

		// Add to list, ensuring duplicates aren't added
		if (!in_array($table, $this->selects['tables'])) {
			$this->selects['tables'][] = $table;
		}
	}

	/*
	# @method addClause( int $clause, string $fieldName, [mixed $fieldValue, [bool $valueIsReference]] )
	# $clause		= Logic clause (see 'clause constants' above)
	# $fieldName	= Field name
	# $fieldValue	= Field value
	# $valueIsReference	= If TRUE then $fieldValue is assumed to be a column reference rather than a literal value
	#
	# Add a clause to the WHERE portion of the query.
	*/
	public function addClause($clause, $fieldName, $fieldValue = null, $valueIsReference = false)
	{

		// Add the clause to the root clause group
		if (func_num_args() < 3) {
			return $this->whereGroup->addClause($clause, $fieldName);
		} else {
			return $this->whereGroup->addClause($clause, $fieldName, $fieldValue, $valueIsReference);
		}
	}

	/*
	# @method void public addClauseLiteral( string $clause )
	# $clause	= Literal clause
	#
	# Sometimes you need to add clauses that refer to column identifiers, such
	# as "WHERE table1.id=table2.other_id ...". This method allows you to define
	# such clauses.
	*/
	public function addClauseLiteral($clause)
	{
		return $this->whereGroup->addClauseLiteral($clause);
	}

	/*
	# @method addHavingClause( int $clause, string $fieldName, mixed $fieldValue, [bool $valueIsReference] )
	# $clause		= Logic clause (see 'clause constants' above)
	# $fieldName	= Field name
	# $fieldValue	= Field value
	# $valueIsReference	= If TRUE then $fieldValue is assumed to be a column reference rather than a literal value
	#
	# Add a clause to the HAVING portion of the query.
	*/
	public function addHavingClause($clause, $fieldName, $fieldValue, $valueIsReference = false)
	{
		return $this->havingGroup->addClause($clause, $fieldName, $fieldValue, $valueIsReference);
	}

	/*
	# @method void setRange( int $start, int $recordCount )
	# $start		= Record at which to start the returned range
	# $recordCount	= Number of records to return
	#
	# Sets the limiting clause. Omit both arguments to clear an existing range.
	*/
	public function setRange($start = null, $recordCount = null)
	{
		if ($start === null && $recordCount === null) {
			$this->limit = null;
		} else {
			$this->limit = [
				'start' => $start,
				'recordCount' => $recordCount - $start
			];
		}
	}

	/**
	 * Returns the defined range.
	 *
	 * @return NULL|array
	 */
	public function getRange()
	{
		return $this->limit;
	}

	/*
	# @method void addOrder( string $fieldName, [string $direction] )
	# $fieldName	= Field name
	# $direction	- Ordering direction (ASC or DESC)
	#
	# Adds an ordering clause.
	*/
	public function addOrder($fieldName, $direction = 'ASC')
	{
		$this->orders[] = [
			'fieldName' => $fieldName,
			'direction' => $direction
		];
	}

	/*
	# @method void leftJoin( string $table, string $clause )
	# $table	= Table name
	# $clause	= Joining clause (eg. "person.job_id=job.id"
	#
	# LEFT JOIN the specified table.
	*/
	public function leftJoin($table, $clause)
	{
		$this->leftJoins[] = [
			'table' => $table,
			'clause' => $clause
		];
	}

	/*
	# @method void rightJoin( string $table, string $clause )
	# $table	= Table name
	# $clause	= Joining clause (eg. "person.job_id=job.id"
	#
	# RIGHT JOIN the specified table.
	*/
	public function rightJoin($table, $clause)
	{
		$this->rightJoins[] = [
			'table' => $table,
			'clause' => $clause
		];
	}

	public function leftJoinSubquery(ModelCriteria $criteria, $alias, $clause)
	{
		$sql = $criteria->sql();
		$this->leftJoins[] = [
			'table' => sprintf('(%s) AS %s', $sql->query, $alias),
			'clause' => $clause,
			'bindings' => $sql->bindings
		];
	}

	public function rightJoinSubquery(ModelCriteria $criteria, $alias, $clause)
	{
		$sql = $criteria->sql();
		$this->rightJoins[] = [
			'table' => sprintf('(%s) AS %s', $sql->query, $alias),
			'clause' => $clause,
			'bindings' => $sql->bindings
		];
	}

	/*
	# @method void innerJoin( string $table, string $clause )
	# $table	= Table name
	# $clause	= Joining clause (will be added to the WHERE portion)
	#
	# Adds necessary elements to join the specified table.
	# $clause is a literal string, meaning it will be added to the SQL exactly
	# as provided here.
	*/
	public function innerJoin($table, $clause)
	{
		$this->selectTable($table);
		$this->addClauseLiteral($clause);
	}

	/**
	 * Returns a JSON representation of this instance for portability.
	 *
	 * @return string
	 */
	public function exportJson()
	{
		return json_encode((object) [
			'selects' => $this->selects,
			'whereGroup' => $this->whereGroup->exportJson(),
			'leftJoins' => $this->leftJoins,
			'rightJoins' => $this->rightJoins,
			'groupBys' => $this->groupBys,
			'havingGroup' => $this->havingGroup->exportJson(),
			'order' => $this->orders,
			'limit' => $this->limit
		]);
	}

	/*
	# @method void groupBy( string $field )
	# $field	= Field name (eg. "age" or "table_name.age"
	#
	# Adds a GROUP BY clause.
	*/
	public function groupBy($field)
	{
		$this->groupBys[] = $field;
	}

	/*
	 # @method useIndex( string $field )
	 # $index	= Index name
	 #
	 # Adds an index to the USE INDEX list.
	 #
	 # Warning: MySQL specific code
	*/
	public function useIndex($index)
	{
		$this->useIndex[] = $index;
	}

	/*
	 # @method disableCache( bool $value )
	 # $value	= Boolean value
	 #
	 # When true, sets caching disabled.
	 #
	 # Warning: MySQL specific code
	*/
	public function disableCache($setting)
	{
		$this->disableCache = $setting;
	}

	/*
	# @method void ungroupBy( [string $field] )
	# $field	= Field name (eg. "age" or "table_name.age"
	#
	# Removes a GROUP BY clause, or if $field is not omitted then all GROUP BY
	# clauses are removed.
	*/
	public function ungroupBy($field = null)
	{
		if ($field === null) {
			$this->groupBys = [];
		} else {
			$this->groupBys = array_diff($this->groupBys, [$field]);
		}
	}

	/*
	# @method string sql()
	#
	# Generates and returns an object containing and SQL query and any variable
	# bindings:
	#	return {
	#		query: 'actual query',
	#		bindings: array(
	#			'param-name': 'param-value',
	#			...
	#		)
	#	}
	*/
	public function sql()
	{

		// Vars
		$sql = new \stdClass();
		$sql->query = '';
		$sql->bindings = [];

		// Generate WHERE portion from the root clause group and all sub-groups
		$whereSql = $this->whereGroup->sql();
		$whereSql->query = str_replace("()", "", $whereSql->query);
		if ($whereSql->query != '') {
			$whereSql->query = preg_replace("/\((.*)\)$/", "$1", $whereSql->query);
			$sql->query = " WHERE {$whereSql->query}";
			$sql->bindings = array_merge($sql->bindings, $whereSql->bindings);
		}

		// Add GROUP BYs
		if (!empty($this->groupBys)) {
			$sql->query .= ' GROUP BY ' . implode(", ", $this->groupBys);
		}

		// Add HAVING clauses
		$havingSql = $this->havingGroup->sql();
		$havingSql->query = str_replace("()", "", $havingSql->query);
		if ($havingSql->query != '') {
			$sql->query .= " HAVING {$havingSql->query}";
			$sql->bindings = array_merge($sql->bindings, $havingSql->bindings);
		}

		// Add ORDER BYs
		if (count($this->orders) > 0) {
			$sql->query .= ' ORDER BY ';
			$orders = [];
			foreach ($this->orders as $order) {
				$orders[] = $order['fieldName'] . ' ' . $order['direction'];
			}
			$sql->query .= implode(", ", $orders);
		}

		// Add LIMIT
		if (!is_null($this->limit)) {
			$sql->query .= ' LIMIT ' . $this->limit['recordCount'] . ' OFFSET ' . $this->limit['start'];
		}

		// Process aggregate subqueries
		if (!empty($this->aggregateSubqueries)) {
			$queries = $this->aggregateSubqueries;
			foreach ($queries as $query) {
				$sql->query = ' LEFT JOIN (' . $query->sql()->query . ') AS ' .
					$query->getName() . ' ON ' . $query->getJoinCriterion() .
					$sql->query;

				$fields = $query->getFields();

				foreach ($fields as $field) {
					$this->selectField("COALESCE(" . $query->getName() . '.' .
						$field['name'] . ", 0)", $field['name']);
				}
			}
		}

		// Add LEFT JOINs
		if (!empty($this->leftJoins)) {
			$joins = array_reverse($this->leftJoins);
			foreach ($joins as $join) {
				$sql->query = ' LEFT JOIN ' . $join['table'] . ' ON ' . $join['clause'] . ' ' . $sql->query;
				if (isset($join['bindings'])) {
					$sql->bindings = array_merge($sql->bindings, $join['bindings']);
				}
			}
		}

		// Add RIGHT JOINs
		if (!empty($this->rightJoins)) {
			$joins = array_reverse($this->rightJoins);
			foreach ($joins as $join) {
				$sql->query = ' RIGHT JOIN ' . $join['table'] . ' ON ' . $join['clause'] . ' ' . $sql->query;
				if (isset($join['bindings'])) {
					$sql->bindings = array_merge($sql->bindings, $join['bindings']);
				}
			}
		}

		// Add a USE INDEX list if any indices exist
		if (!empty($this->useIndex)) {
			$useIndexList = '';
			$useIndexList = ' USE INDEX (' . implode(", ", $this->useIndex) . ') ';
			$sql->query = $useIndexList . $sql->query;
		}

		// Add SELECT fields
		if (!empty($this->selects['fields'])) {
			$fields = [];
			foreach ($this->selects['fields'] as $field) {
				$fields[] = "{$field->query} " . ($field->alias === null ? '' : " AS {$field->alias}");
				if ($field->bindings !== null) {
					$sql->bindings = array_merge($sql->bindings, $field->bindings);
				}
			}
			$tables = [];
			if (!empty($this->selects['tables'])) {
				foreach ($this->selects['tables'] as $t) {
					if ($t instanceOf StdClass) {
						$tables[] = "{$t->table} " . (!empty($t->alias) ? " AS {$t->alias}" : '');
						if ($t->bindings !== null) {
							$sql->bindings = array_merge($sql->bindings, $t->bindings);
						}
					} else {
						$tables[] = $t;
					}
				}
			}
			$noCache = $this->disableCache ? 'SQL_NO_CACHE ' : '';

			//$sql->query = 'SELECT '.implode(", ", $fields).' '.(!empty($this->selects['tables']) ? 'FROM '.implode(", ", $this->selects['tables']).' ' : '').$sql->query;
			$sql->query = 'SELECT ' . $noCache . implode(", ", $fields) . ' ' . (!empty($tables) ? 'FROM ' . implode(", ", $tables) . ' ' : '') . $sql->query;
		}

		// Result
		return $sql;
	}

	/*
	# @method void __clone()
	#
	# Clone properties.
	*/
	public function __clone()
	{
		$this->whereGroup = clone $this->whereGroup;
		$this->havingGroup = clone $this->havingGroup;
	}
}

?>
