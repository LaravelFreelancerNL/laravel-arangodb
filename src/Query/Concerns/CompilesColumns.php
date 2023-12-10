<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Exception;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Query\Builder;

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
        assert($query instanceof Builder);

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

        $returnValues = $this->determineReturnValues($query, $returnAttributes, $returnDocs);

        $return = 'RETURN ';
        if ($query->distinct) {
            $return .= 'DISTINCT ';
        }

        return $return . $returnValues;
    }

    /**
     * @throws Exception
     */
    protected function normalizeColumn(IlluminateQueryBuilder $query, mixed $column, string $table = null): mixed
    {
        assert($query instanceof Builder);

        if ($column instanceof Expression) {
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

        if ($query->isVariable($column)) {
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
     * @param Builder $query
     * @param string $column
     * @param string|null $table
     * @return string
     */
    protected function normalizeColumnReferences(IlluminateQueryBuilder $query, string $column, string $table = null): string
    {
        if ($query->isReference($column)) {
            return $column;
        }

        if ($table == null) {
            $table = $query->from;
        }

        $references = explode('.', $column);


        $tableAlias = $query->getTableAlias($references[0]);

        if (isset($tableAlias)) {
            $references[0] = $tableAlias;
        }

        if (array_key_exists('groupsVariable', $query->tableAliases)) {
            $tableAlias = 'groupsVariable';
            array_unshift($references, $tableAlias);
        }

        // check table alias for first element
        // not just aliases but also variables.


        if ($tableAlias === null  && array_key_exists($table, $query->tableAliases)) {
            array_unshift($references, $query->tableAliases[$table]);
        }

        if ($tableAlias === null && !$query->isReference($references[0])) {
            $tableAlias = $query->generateTableAlias($table);
            array_unshift($references, $tableAlias);
        }

        return implode('.', $references);
    }


    protected function determineReturnValues($query, $returnAttributes = [], $returnDocs = []): string
    {
        if (empty($returnAttributes) && empty($returnDocs)) {
            $returnDocs[] = $query->getTableAlias($query->from);
            // FIXME: shouldn't this be dependent on the selected documents & attributes?
            //            if (is_array($query->joins) && !empty($query->joins)) {
            //                $values = $this->mergeJoinResults($query, $values);
            //            }
        }

        if ($query->aggregate !== null) {
            $returnDocs = [];
        }

        $returnAttributesObjectString = $this->mergeReturnAttributes($query, $returnAttributes, $returnDocs);

        $values = $this->mergeReturnDocs($returnAttributesObjectString, $returnAttributes, $returnDocs);

        return $values;
    }

    protected function mergeReturnAttributes(IlluminateQueryBuilder $query, $returnAttributes, $returnDocs)
    {
        $values = [];

        if ($query->aggregate !== null) {
            $returnAttributes = ['`aggregate`' => 'aggregateResult'];
        }

        // If there is just one attribute/column given we assume that you want a list of values
        //  instead of a list of objects
        // FIXME: nope???
        if ($query->aggregate === null && count($returnAttributes) == 1 && empty($returnDocs)) {
            return reset($returnAttributes);
        }

        if (!empty($returnAttributes)) {
            return $this->generateAqlObject($returnAttributes);
        }

        return null;
    }

    protected function mergeReturnDocs($values, $returnAttributeObject, $returnDocs)
    {
        if (!empty($values) && !empty($returnDocs)) {
            $returnDocs[] = $values;
        }

        if (sizeOf($returnDocs) > 1) {
            return 'MERGE(' . implode(', ', $returnDocs) . ')';
        }
        if (sizeOf($returnDocs) === 1) {
            return $returnDocs[0];
        }

        return $values;
    }

    protected function mergeJoinResults(IlluminateQueryBuilder $query, $baseTable): string
    {
        assert($query instanceof Builder);

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
