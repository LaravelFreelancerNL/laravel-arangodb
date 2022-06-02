<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Exception;
use Illuminate\Database\Query\Builder as IlluminateBuilder;
use Illuminate\Support\Arr;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\Expressions\FunctionExpression;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

trait CompilesColumns
{
    /**
     * Compile the "select *" portion of the query.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param IlluminateBuilder $query
     * @param array<mixed> $columns
     * @return string|null
     * @throws Exception
     */
    protected function compileColumns(IlluminateBuilder $query, $columns)
    {
        $returnDocs = [];
        $returnAttributes = [];

        // Prepare columns
        foreach ($columns as $key => $column) {
            // Extract rows
            if (is_string($column) && substr($column, strlen($column) - 2)  === '.*') {
                $table = substr($column, 0, strlen($column) - 2);
                $returnDocs[] = $this->getTableAlias($table);

                continue;
            }

            // Extract groups
            if (is_array($query->groups) && in_array($column, $query->groups)) {
                $returnAttributes[$key] = $column;

                continue;
            }

            if (is_string($column) && $column != null && $column != '*') {
                [$column, $alias] = $this->normalizeStringColumn($query, $key, $column);

                if (isset($returnAttributes[$alias]) && is_array($column)) {
                    $normalizedColumn = $this->normalizeColumn($query, $column);

                    if (is_array($normalizedColumn)) {
                        foreach ($normalizedColumn as $key => $value) {
                            $returnAttributes[$alias][$key] = $value;
                        }
                    }
                }
                $returnAttributes[$alias] = $column;
            }
        }

        $values = $this->determineReturnValues($query, $returnAttributes, $returnDocs);

        $aql = 'RETURN';
        if ((bool) $query->distinct) {
            $aql .= ' DISTINCT';
        }
        $aql .= ' ' . $this->compileValuesToAql($values);

        return $aql;
    }

    protected function compileValuesToAql(mixed $values): string
    {
        if (is_string($values)) {
            return $values;
        }

        $compiledValues = '{';
        foreach ($values as $key => $value) {
            if (is_array($value) && Arr::isAssoc($value)) {
                $value = $this->compileValuesToAql($value);
            }
            $compiledValues .= "{$key}: {$value}, ";
        }
        $compiledValues = substr($compiledValues, 0, strlen($compiledValues) - 2);
        $compiledValues .= '}';

        return $compiledValues;
    }


    /**
     * @throws Exception
     */
    protected function normalizeColumn(IlluminateBuilder $query, mixed $column, string $table = null): mixed
    {
        if ($column instanceof QueryBuilder || $column instanceof FunctionExpression) {
            return $column;
        }

        $column = $this->convertColumnId($column);

        if (
            is_array($query->groups)
            && in_array($column, $query->groups)
            && debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT)[1]['function'] !== "compileGroups"
        ) {
            return $column;
        }

        if (is_array($column)) {
            foreach ($column as $key => $value) {
                if (! is_string($value)) {
                    $column[$key] = $this->normalizeColumn($query, $value, $table);
                }

                if (is_string($value)) {
                    [$subColumn, $alias] = $this->normalizeStringColumn($query, $key, $value);
                    $column[$alias] = $subColumn;
                }
            }

            return $column;
        }

        if (key_exists($column, $query->variables)) {
            return $column;
        }

        //We check for an existing alias to determine of the first reference is a table.
        // In which case we replace it with the alias.
        return $this->normalizeColumnReferences($query, $column, $table);
    }

    /**
     * @return array<mixed>
     * @throws Exception
     */
    protected function normalizeStringColumn(IlluminateBuilder $query, int|string $key, string $column): array
    {
        [$column, $alias] = $this->extractAlias($column, $key);

        if (! isDotString($alias)) {
            $column = $this->normalizeColumn($query, $column);

            return [$column, $alias];
        }

        $column = Arr::undot([$column => $column]);
        $alias = array_key_first($column);
        $column = $column[$alias];

        return [$column, $alias];
    }


    /**
     * @param Builder $query
     * @param string $column
     * @param string|null $table
     * @return string
     */
    protected function normalizeColumnReferences(Builder $query, string $column, string $table = null): string
    {
        if ($table == null) {
            $table = $query->from;
        }

        // Replace SQL JSON arrow for AQL dot
        $column = str_replace('->', '.', $column);

        $references = explode('.', $column);


        $tableAlias = $this->getTableAlias($references[0]);
        if (isset($tableAlias)) {
            $references[0] = $tableAlias;
        }

        if ($tableAlias === null && $table != null && ! $this->isTableAlias($references[0])) {
            $tableAlias = $this->generateTableAlias($table);
            array_unshift($references, $tableAlias);
        }

        return $this->wrap(implode('.', $references));
    }


    protected function determineReturnValues($query, $returnAttributes = [], $returnDocs = [])
    {
        $values = $this->mergeReturnAttributes($returnAttributes, $returnDocs);

        $values = $this->mergeReturnDocs($values, $query, $returnAttributes, $returnDocs);

        if ($query->aggregate !== null) {
            $values = ['aggregate' => 'aggregateResult'];
        }

        if (empty($values)) {
            $values = $this->getTableAlias($query->from);
            if (is_array($query->joins) && !empty($query->joins)) {
                $values = $this->mergeJoinResults($query, $values);
            }
        }

        return $values;
    }

    protected function mergeReturnAttributes($returnAttributes, $returnDocs)
    {
        $values = [];
        if (! empty($returnAttributes)) {
            $values = $returnAttributes;
        }

        // If there is just one attribute/column given we assume that you want a list of values
        //  instead of a list of objects
        if (count($returnAttributes) == 1 && empty($returnDocs)) {
            $values = reset($returnAttributes);
        }

        return $values;
    }

    protected function mergeReturnDocs($values, $query, $returnAttributes, $returnDocs)
    {
        if (! empty($returnAttributes) && ! empty($returnDocs)) {
            $returnDocs[] = $returnAttributes;
        }

        if (! empty($returnDocs)) {
            $values = $query->aqb->merge(...$returnDocs);
        }
        return $values;
    }


    protected function mergeJoinResults($query, $baseTable)
    {
        $tablesToJoin = [];
        foreach ($query->joins as $key => $join) {
            $tableAlias = $this->getTableAlias($join->table);

            if (! isset($tableAlias)) {
                $tableAlias = $this->generateTableAlias($join->table);
            }
            $tablesToJoin[$key] = $tableAlias;
        }

        $tablesToJoin = array_reverse($tablesToJoin);
        $tablesToJoin[] = $baseTable;

        return $query->aqb->merge(...$tablesToJoin);
    }
}
