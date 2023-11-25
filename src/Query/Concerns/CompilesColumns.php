<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Exception;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

trait CompilesColumns
{
    /**
     * Compile the "select *" portion of the query.
     *
     * @param IlluminateQueryBuilder $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(IlluminateQueryBuilder $query, $columns)
    {
        $returnDocs = [];
        $returnAttributes = [];

        $columns = $this->convertJsonFields($columns);

        // Prepare columns
        foreach ($columns as $key => $column) {
            // Extract complete documents
            if (is_string($column) && substr($column, strlen($column) - 2)  === '.*') {
                $table = substr($column, 0, strlen($column) - 2);
                $returnDocs[] = $query->getTableAlias($table);

                continue;
            }

            // Extract groups
            if (is_array($query->groups) && in_array($column, $query->groups)) {
                $returnAttributes[$key] = $this->normalizeColumn($query, $column);

                continue;
            }

            if (is_string($column) && $column != null && $column != '*') {
                [$column, $alias] = $this->normalizeStringColumn($query, $key, $column);

                if (isset($returnAttributes[$alias]) && is_array($column)) {
                    $returnAttributes[$alias] = array_merge_recursive(
                        $returnAttributes[$alias],
                        $this->normalizeColumn($query, $column)
                    );
                    continue;
                }
                $returnAttributes[$alias] = $column;
            }
        }

        $values = $this->determineReturnValues($query, $returnAttributes, $returnDocs);

        $return = 'RETURN ';
        if ($query->distinct) {
            $return .= 'DISTINCT ';
        }

        return $return . $values;
    }

    /**
     * @throws Exception
     */
    protected function normalizeColumn(IlluminateQueryBuilder $query, mixed $column, string $table = null): mixed
    {
        if ($column instanceof QueryBuilder || $column instanceof Expression) {
            return $column;
        }

        if (
            is_array($query->groups)
            && in_array($column, $query->groups)
            && debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT)[1]['function'] !== "compileGroups"
        ) {
            return $this->wrap($query->convertIdToKey($column));
        }

        if (is_array($column)) {
            $column = $this->convertJsonFields($column);

            foreach ($column as $key => $value) {
                $column[$key] = $this->normalizeColumn($query, $value, $table);
            }
            return $column;
        }

        if (key_exists($column, $query->variables)) {
            return $this->wrap($column);
        }


        $column = $this->convertJsonFields($column);
        $column = $query->convertIdToKey($column);

        //We check for an existing alias to determine of the first reference is a table.
        // In which case we replace it with the alias.
        return $this->wrap($this->normalizeColumnReferences($query, $column, $table));
    }

    /**
     * @param string $column
     * @return array<mixed>
     * @throws Exception
     */
    protected function normalizeStringColumn(Builder $query, int|string $key, string $column, string $table = null): array
    {
        [$column, $alias] = $query->extractAlias($column, $key);

        $column = $query->convertIdToKey($column);

        $column = $this->wrap($this->normalizeColumnReferences($query, $column, $table));

        return [$column, $alias];
    }


    /**
     * @param Builder $builder
     * @param string $column
     * @param string|null $table
     * @return string
     */
    protected function normalizeColumnReferences(IlluminateQueryBuilder $query, string $column, string $table = null): string
    {
        if ($table == null) {
            $table = $query->from;
        }

        $references = explode('.', $column);

        $tableAlias = $query->getTableAlias($references[0]);
        if (isset($tableAlias)) {
            $references[0] = $tableAlias;
        }

        if ($tableAlias === null && $table != null && !$query->isTableAlias($references[0])) {
            $tableAlias = $query->generateTableAlias($table);
            array_unshift($references, $tableAlias);
        }

        return implode('.', $references);
    }


    protected function determineReturnValues($query, $returnAttributes = [], $returnDocs = []): string
    {
        $values = $this->mergeReturnAttributes($returnAttributes, $returnDocs);

        $values = $this->mergeReturnDocs($values, $returnAttributes, $returnDocs);

        if ($query->aggregate !== null) {
            $values = ['`aggregate`' => 'aggregateResult'];
        }

        if (empty($values)) {
            $values = $query->getTableAlias($query->from);
            if (is_array($query->joins) && !empty($query->joins)) {
                $values = $this->mergeJoinResults($query, $values);
            }
        }

        if (is_array($values)) {
            $values = $this->generateAqlObject($values);
        }

        return $values;
    }

    protected function formatReturnData($values)
    {
        $object = "";
        foreach ($values as $key => $value) {
            if ($object !== "") {
                $object .= ", ";
            }
            if (is_array($value)) {
                $value = $this->formatReturnData($value);
            }

            if (array_is_list($values)) {
                $object .= $value;
                continue;
            }

            $object .= $key . ': ' . $value;
        }

        if (array_is_list($values)) {
            return '[' . $object . ']';
        }
        return '{' . $object . '}';
    }

    protected function mergeReturnAttributes($returnAttributes, $returnDocs)
    {
        $values = [];
        if (!empty($returnAttributes)) {
            $values = $returnAttributes;
        }

        // If there is just one attribute/column given we assume that you want a list of values
        //  instead of a list of objects
        if (count($returnAttributes) == 1 && empty($returnDocs)) {
            $values = reset($returnAttributes);
        }

        return $values;
    }

    protected function mergeReturnDocs($values, $returnAttributes, $returnDocs)
    {
        if (!empty($returnAttributes) && !empty($returnDocs)) {
            $returnDocs[] = $returnAttributes;
        }

        if (!empty($returnDocs)) {
            $returnDocString = implode(', ', $returnDocs);
            $values = `MERGE($returnDocString)`;
        }
        return $values;
    }

    protected function mergeJoinResults(IlluminateQueryBuilder $query, $baseTable): string
    {
        $tablesToJoin = [];
        foreach ($query->joins as $key => $join) {
            $tableAlias = $query->getTableAlias($join->table);

            if (!isset($tableAlias)) {
                $tableAlias = $query->generateTableAlias($join->table);
            }
            $tablesToJoin[$key] = $tableAlias;
        }

        $tablesToJoin = array_reverse($tablesToJoin);
        $tablesToJoin[] = $baseTable;

        return 'MERGE(' . implode(', ', $tablesToJoin) . ')';
    }
}
