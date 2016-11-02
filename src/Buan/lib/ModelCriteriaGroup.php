<?php
/**
 * @package Buan
 */
namespace Buan;

use \PDO;
use \stdClass;

class ModelCriteriaGroup
{

    /*
     * @property array $clauses
     * Clauses.
    */
    private $clauses = [];

    /*
     * @property array $groups
     * Sub-groups.
    */
    private $groups = [];

    /*
     * @property string $logic
     * Evaluation logic used within this group.
    */
    private $logic = ModelCriteria::LOGIC_AND;

    /*
     * @method void __construct( [string $logic] )
     * $logic	= Logic to be used in this group (AND, OR)
    */
    public function __construct($logic = ModelCriteria::LOGIC_AND)
    {

        // Set
        $this->logic = $logic;
    }

    /*
     * @method void setLogic( string $logic )
     * $logic	= Logic (AND, OR)
     *
     * Sets the logical evaluation method for this group.
    */
    public function setLogic($logic)
    {

        // Set
        $this->logic = $logic;
    }

    /*
     * @property void addClause( int $type, string $fieldName, [mixed|array $fieldValue, [bool $valueIsReference]] )
     * $type			= Clause (see ModelCriteria 'clause constants')
     * $fieldName	= Field name
     * $fieldValue	= Field value (see notes below)
     * $valueIsReference	= If TRUE then $fieldValue is assumed to be a column reference rather than a literal value
     *
     * Adds a clause to this group.
     *
     * For most cases $fieldValue will be a string/integer/etc/etc, for which the
     * data-type will be defaulted to string (PDO::PARAM_STR). However, if may
     * explicitly define the data-type (see PDO::PARAM_* constants) by passing
     * $fieldValue as a 2-element array; the first is the actual value, and the
     * second is the data-type.
     *
     * Special case, ModelCriteria::IN, ModelCriteria::NOT_IN ...
     * When using this clause type, $fieldValue is expected to be an array. The
     * elements in this array are all assumed to be PDO::PARAM_STR (strings) and
     * will be concatenated in the generated SQL as a comma separate list of
     * strings. eg.
     *	$fieldValue = array('bob', 7, 'axel', 8.90);
     *	sql: ... colname IN ('bob', '7', 'axel', '8.90') ...
    */
    public function addClause($type, $fieldName, $fieldValue = null, $valueIsReference = false)
    {

        // TODO:
        // Allow $fieldValue to be an array if you want to specify a datatype, $fieldValue = (value, dataType (PDO const)
        // What about ModelCriteria::IN and ModelCriteria::NOT_IN clause types - they use an array already (so should FIND_IN_SET for consistency.
        // Therefore, first check the clause type, then $fieldValue type.
        // Or, perhaps, if clause type is an array, first change it to a comma-separated list of parameter bindings, each one
        // being treated as a string (the default)

        // Prepare the clause object
        $clause = new stdClass();
        if ($valueIsReference || func_num_args() < 3) {
            $clause->binding = null;
            $value = $fieldValue;
        } else {
            $clause->binding = new stdClass();
            if ($type === ModelCriteria::IN || $type === ModelCriteria::NOT_IN) {
                $clause->binding = [];
                $paramPrefix = ':p' . md5(uniqid(rand()));
                $value = [];
                foreach ($fieldValue as $i => $v) {
                    $nb = new stdClass();
                    $nb->value = $v;
                    $nb->dataType = PDO::PARAM_STR;
                    $nb->parameter = $paramPrefix . '_' . $i;
                    $clause->binding[] = $nb;
                    $value[] = $nb->parameter;
                }
                $value = implode(", ", $value);
            } else {
                if ($type === ModelCriteria::BETWEEN) {
                    $clause->binding = [];
                    $paramPrefix = ':p' . md5(uniqid(rand()));
                    $value = [];
                    $fieldValue = array_splice($fieldValue, 0, 2);
                    foreach ($fieldValue as $i => $v) {
                        $nb = new stdClass();
                        $nb->value = $v;
                        $nb->dataType = PDO::PARAM_STR;
                        $nb->parameter = $paramPrefix . '_' . $i;
                        $clause->binding[] = $nb;
                        $value[] = $nb->parameter;
                    }
                    $value = implode(" AND ", $value);
                } else {
                    if (is_array($fieldValue)) {
                        $clause->binding->value = $fieldValue[0];
                        $clause->binding->dataType = $fieldValue[1];
                        $value = $clause->binding->parameter = ':p' . md5(uniqid(rand()));
                    } else {
                        $clause->binding->value = $fieldValue;
                        $clause->binding->dataType = PDO::PARAM_STR;
                        $value = $clause->binding->parameter = ':p' . md5(uniqid(rand()));
                    }
                }
            }
        }
        $clause->expression = null;
        $clause->fieldName = $fieldName;
        $clause->type = $type;

        // Act on clause type
        switch ($type) {
            case ModelCriteria::EQUALS:
                $clause->expression = "$fieldName=$value";
                break;
            case ModelCriteria::NOT_EQUALS:
                $clause->expression = "$fieldName<>$value";
                break;
            case ModelCriteria::LIKE:
                $clause->expression = "$fieldName LIKE $value";
                break;
            case ModelCriteria::NOT_LIKE:
                $clause->expression = "$fieldName NOT LIKE $value";
                break;
            case ModelCriteria::GREATER_THAN:
                $clause->expression = "$fieldName>$value";
                break;
            case ModelCriteria::GREATER_THAN_OR_EQUAL:
                $clause->expression = "$fieldName>=$value";
                break;
            case ModelCriteria::LESS_THAN:
                $clause->expression = "$fieldName<$value";
                break;
            case ModelCriteria::LESS_THAN_OR_EQUAL:
                $clause->expression = "$fieldName<=$value";
                break;
            case ModelCriteria::IS_NULL:
                $clause->expression = "$fieldName IS NULL";
                break;
            case ModelCriteria::IS_NOT_NULL:
                $clause->expression = "$fieldName IS NOT NULL";
                break;
            case ModelCriteria::FIND_IN_SET:
                $clause->expression = "FIND_IN_SET($value, $fieldName)";
                break;
            case ModelCriteria::IN:
                $clause->expression = "$fieldName IN ($value)";
                break;
            case ModelCriteria::NOT_IN:
                $clause->expression = "$fieldName NOT IN ($value)";
                break;
            case ModelCriteria::BETWEEN:
                $clause->expression = "$fieldName BETWEEN $value";
                break;
            default:
                return;
                break;
        }
        $this->clauses[] = $clause;
    }

    /*
     * @method void addClauseLiteral( string $string )
     * $string	= Literal clause, eg. "name=tbl2.name", "salary>67+col2.avg"
     *
     * Adds a custom SQL string clause.
    */
    public function addClauseLiteral($string)
    {
        $clause = new stdClass();
        $clause->expression = $string;
        $clause->binding = null;
        $this->clauses[] = $clause;
    }

    /*
     * @method ModelCriteriaGroup( [string $logic] )
     * $logic	= Logic
     *
     * Adds a sub-group to this group.
    */
    public function addGroup($logic = ModelCriteria::LOGIC_AND)
    {

        // Create and return the new sub-group
        $group = new ModelCriteriaGroup($logic);
        return $this->groups[] = $group;
    }

    /**
     * Runs $this criteria over the given Models and returns a ModelCollection
     * containing all that satisfied the criteria.
     *
     * @param Model|ModelCollection Models to which $this criteria will be applied
     * @return null|ModelCollection
     */
    public function applyTo($models)
    {

        // Reduce matches by applying to all subgroups first
        $matches = [];
        foreach ($this->groups as $g) {
            $matches = $g->applyTo($models)->asArray();
        }

        // Now execute each clause on each model to determine if it matches ALL (or
        // SOME in the case of LOGIC_OR)
        foreach ($models as $model) {
            $isMatch = $this->logic === ModelCriteria::LOGIC_AND ? true : false;
            foreach ($this->clauses as $k => $c) {
                $clauseMatch = true;
                switch ($c->type) {
                    case ModelCriteria::EQUALS:
                        if ($model->{$c->fieldName} != $c->binding->value) {
                            $clauseMatch = false;
                        }
                        break;
                    case ModelCriteria::NOT_EQUALS:
                        if (!$model->{$c->fieldName} == $c->binding->value) {
                            $clauseMatch = false;
                        }
                        break;
                    case ModelCriteria::LIKE:
                        // TODO
                        break;
                    case ModelCriteria::NOT_LIKE:
                        // TODO
                        break;
                    case ModelCriteria::GREATER_THAN:
                        if ($model->{$c->fieldName} <= $c->binding->value) {
                            $clauseMatch = false;
                        }
                        break;
                    case ModelCriteria::LESS_THAN:
                        if ($model->{$c->fieldName} >= $c->binding->value) {
                            $clauseMatch = false;
                        }
                        break;
                    case ModelCriteria::LESS_THAN_OR_EQUAL:
                        if ($model->{$c->fieldName} > $c->binding->value) {
                            $clauseMatch = false;
                        }
                        break;
                    case ModelCriteria::IS_NULL:
                        if ($model->{$c->fieldName} !== null) {
                            $clauseMatch = false;
                        }
                        break;
                    case ModelCriteria::IS_NOT_NULL:
                        if ($model->{$c->fieldName} === null) {
                            $clauseMatch = false;
                        }
                        break;
                    case ModelCriteria::FIND_IN_SET:
                        // TODO
                        break;
                    case ModelCriteria::IN:
                        // TODO
                        break;
                    case ModelCriteria::NOT_IN:
                        // TODO
                        break;
                    default:
                        return null;
                        break;
                }
                $isMatch = $this->logic === ModelCriteria::LOGIC_AND ? $isMatch && $clauseMatch : $isMatch || $clauseMatch;
            }

            // It's a match, so add it to the final list
            if ($isMatch) {
                $matches[] = $model;
            }
        }
        return new ModelCollection($matches);
    }

    /**
     * Returns a JSON representation of this instance for portability.
     *
     * @return string
     */
    public function exportJson()
    {
        $groups = [];
        foreach ($this->groups as $g) {
            $groups[] = $g->exportJson();
        }
        return json_encode((object) [
            'clauses' => $this->clauses,
            'groups' => $groups,
            'logic' => $this->logic
        ]);
    }

    /*
     * @method object sql()
     *
     * Generate and return this group's, and all sub-groups', SQL query and
     * bindings in an object in the format:
     * return {
     *	query->'the string query containing parameter placeholders',
     *	bindings->array(
     *		'parameter-tag'=>'field-value',
     *		...
     *	)
     * }
     */
    public function sql()
    {

        $sql = new stdClass();
        $sql->query = '';
        $sql->bindings = [];

        $clauseExpressions = [];
        foreach ($this->clauses as $clause) {
            $clauseExpressions[] = $clause->expression;
            if ($clause->binding !== null) {
                if (is_array($clause->binding)) {
                    // ie. ModelCriteria::IN or ModelCriteria::NOT_IN has been used
                    foreach ($clause->binding as $b) {
                        $sql->bindings[$b->parameter] = $b;
                    }
                } else {
                    $sql->bindings[$clause->binding->parameter] = $clause->binding;
                }
            }
        }
        $sql->query = implode(' ' . $this->logic . ' ', $clauseExpressions);

        foreach ($this->groups as $group) {
            $groupSql = $group->sql();
            if ($groupSql->query != '()') {
                $sql->query .= ($sql->query == '' ? '' : ' ' . $this->logic . ' ') . $groupSql->query;
            }
            $sql->bindings = array_merge($sql->bindings, $groupSql->bindings);
        }

        $sql->query = "({$sql->query})";
        return $sql;
    }

    public function __clone()
    {
        foreach ($this->clauses as $k => $v) {
            $this->clauses[$k] = clone $this->clauses[$k];
        }

        foreach ($this->groups as $k => $v) {
            $this->groups[$k] = clone $this->groups[$k];
        }
    }
}
