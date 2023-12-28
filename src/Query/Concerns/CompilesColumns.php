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
     * @param array $columns
     * @return string|null
     * @throws Exception
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
                $returnAttributes[$column] = $this->normalizeColumn($query, $column);

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

            if (is_string($column) && $column === '*') {
                //TODO place table alias and join aliases in returnDocuments?
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

        $alias = $this->cleanAlias($query, $alias);

        return [$column, $alias];
    }


    /**
     * @param IlluminateQueryBuilder $query
     * @param string $column
     * @param string|null $table
     * @return string
     */
    protected function normalizeColumnReferences(IlluminateQueryBuilder $query, string $column, string $table = null): string
    {
        assert($query instanceof Builder);

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

        if ($tableAlias === null  && array_key_exists($table, $query->tableAliases)) {
            array_unshift($references, $query->tableAliases[$table]);
        }

        if ($tableAlias === null && !$query->isReference($references[0])) {
            $tableAlias = $query->generateTableAlias($table);
            array_unshift($references, $tableAlias);
        }

        return implode('.', $references);
    }

    protected function cleanAlias(IlluminateQueryBuilder $query, int|null|string $alias): int|string|null
    {
        assert($query instanceof Builder);

        if (!is_string($alias)) {
            return $alias;
        }

        if (!str_contains($alias, '.')) {
            return $alias;
        }

        $elements = explode('.', $alias);

        if(
            !$query->isTable($elements[0])
            && !$query->isVariable($elements[0])
        ) {
            return $alias;
        }

        array_shift($elements);

        return implode($elements);
    }


    protected function determineReturnValues($query, $returnAttributes = [], $returnDocs = []): string
    {
        // If nothing was specifically requested, we return everything.
        if (empty($returnAttributes) && empty($returnDocs)) {
            $returnDocs[] = $query->getTableAlias($query->from);

            if ($query->joins !== null) {
                $returnDocs = $this->mergeJoinResults($query, $returnDocs);
            }
        }

        // clean up returnAttributes?

        // Aggregate functions only return the aggregate, so we can clear out everything else.
        if ($query->aggregate !== null) {
            $returnDocs = [];
            $returnAttributes = ['`aggregate`' => 'aggregateResult'];
        }

        // Return a single value for certain subqueries
        if (
            $query->returnSingleValue === true
            && count($returnAttributes) === 1
            && empty($returnDocs)
        ) {
            return reset($returnAttributes);
        }

        if (!empty($returnAttributes)) {
            $returnDocs[] = $this->generateAqlObject($returnAttributes);
        }

        $values = $this->mergeReturnDocs($returnDocs);

        return $values;
    }

    protected function mergeReturnDocs($returnDocs)
    {
        if (sizeOf($returnDocs) > 1) {
            return 'MERGE(' . implode(', ', $returnDocs) . ')';
        }

        return reset($returnDocs);
    }

    protected function mergeJoinResults(IlluminateQueryBuilder $query, $returnDocs = []): array
    {
        assert($query instanceof Builder);

        foreach ($query->joins as $join) {
            $tableAlias = $query->getTableAlias($join->table);

            if (!isset($tableAlias)) {
                $tableAlias = $query->generateTableAlias($join->table);
            }
            $returnDocs[] = $tableAlias;
        }

        return $returnDocs;
    }
}
