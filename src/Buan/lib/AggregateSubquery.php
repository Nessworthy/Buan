<?php
/**
 * This class is intended to simplify complex, but often similar
 * aggregate queries.
 *
 * @package Buan
 */
namespace Buan;

class AggregateSubquery extends ModelCriteria
{
    /*
     * These fields contain the aggregate metadata required
     * for performing the query both inside and outside of
     * the sub-query itself.
     *
     */
    private $fields = [];

    private $name;

    private $joinCriterion;

    public function __construct($table, $groupField, $name, $joinCriterion)
    {
        parent::__construct();
        $this->name = $name;
        $this->selectTable($table);
        $this->selectField($groupField, "group_field");
        $this->groupBy($groupField);
        $this->joinCriterion = $joinCriterion;
    }

    /**
     * Add an aggregate field to the subQuery.
     * @param string $function
     * @param string $condition
     * @param string $name
     */
    public function addAggregate($function, $condition, $name)
    {
        $this->selectField($function . '(NULLIF(' . $condition .
            ', FALSE)) AS ' . $name);

        $field = [
            "name" => $name,
            "function" => $function,
            "condition" => $condition
        ];
        $this->fields[] = $field;
    }

    /**
     * Return name attribute
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return join criterion
     *
     */
    public function getJoinCriterion()
    {
        return $this->joinCriterion;
    }

    public function getFields()
    {
        return $this->fields;
    }
}
