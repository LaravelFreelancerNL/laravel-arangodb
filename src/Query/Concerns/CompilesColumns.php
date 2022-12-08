<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Exception;
use Illuminate\Support\Arr;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\Expressions\FunctionExpression;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

trait CompilesColumns
{
    /**
     * Compile the "RETURN" portion of the query.
     *
     * @param  Builder  $builder
     * @param  array  $columns
     * @return Builder
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function compileColumns(Builder $builder, array $columns): Builder
    {
        $returnDocs = [];
        $returnAttributes = [];

        // Prepare columns
        foreach ($columns as $key => $column) {
            // Extract rows
            if (is_string($column) && substr($column, strlen($column) - 2) === '.*') {
                $table = substr($column, 0, strlen($column) - 2);
                $returnDocs[] = $this->getTableAlias($table);

                continue;
            }

            // Extract groups
            if (is_array($builder->groups) && in_array($column, $builder->groups)) {
                $returnAttributes[$key] = $column;

                continue;
            }

            if (is_string($column) && $column != null && $column != '*') {
                [$column, $alias] = $this->normalizeStringColumn($builder, $key, $column);

                if (isset($returnAttributes[$alias]) && is_array($column)) {
                    $returnAttributes[$alias] = array_merge_recursive(
                        $returnAttributes[$alias],
                        $this->normalizeColumn($builder, $column)
                    );

                    continue;
                }

                $returnAttributes[$alias] = $this->normalizeColumn($builder, $column);
            }
        }

        $values = $this->determineReturnValues($builder, $returnAttributes, $returnDocs);

        $builder->aqb = $builder->aqb->return($values, (bool) $builder->distinct);

        return $builder;
    }

    /**
     * @throws Exception
     */
    protected function normalizeColumn(Builder $builder, mixed $column, string $table = null): mixed
    {
        if ($column instanceof QueryBuilder || $column instanceof FunctionExpression) {
            return $column;
        }

        $column = $this->convertColumnId($column);

        if (
            is_array($builder->groups)
            && in_array($column, $builder->groups)
            && debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT)[1]['function'] !== 'compileGroups'
        ) {
            return $column;
        }

        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $column[$key] = $this->normalizeColumn($builder, $value, $table);
            }

            return $column;
        }

        if (array_key_exists($column, $builder->variables)) {
            return $column;
        }

        //We check for an existing alias to determine of the first reference is a table.
        // In which case we replace it with the alias.
        return $this->normalizeColumnReferences($builder, $column, $table);
    }

    /**
     * @param  array<mixed>  $column
     * @return array<mixed>
     *
     * @throws Exception
     */
    protected function normalizeStringColumn(Builder $builder, int|string $key, mixed $column): array
    {
        [$column, $alias] = $this->extractAlias($column, $key);

        if (! isDotString($alias)) {
            $column = $this->normalizeColumn($builder, $column);

            return [$column, $alias];
        }

        $column = Arr::undot([$column => $column]);
        $alias = array_key_first($column);
        $column = $column[$alias];

        return [$column, $alias];
    }

    /**
     * @param  Builder  $builder
     * @param  string  $column
     * @param  string|null  $table
     * @return string
     */
    protected function normalizeColumnReferences(Builder $builder, string $column, string $table = null): string
    {
        if ($table == null) {
            $table = $builder->from;
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

        return implode('.', $references);
    }

    protected function determineReturnValues($builder, $returnAttributes = [], $returnDocs = [])
    {
        $values = $this->mergeReturnAttributes($returnAttributes, $returnDocs);

        $values = $this->mergeReturnDocs($values, $builder, $returnAttributes, $returnDocs);

        if ($builder->aggregate !== null) {
            $values = ['aggregate' => 'aggregateResult'];
        }

        if (empty($values)) {
            $values = $this->getTableAlias($builder->from);
            if (is_array($builder->joins) && ! empty($builder->joins)) {
                $values = $this->mergeJoinResults($builder, $values);
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

    protected function mergeReturnDocs($values, $builder, $returnAttributes, $returnDocs)
    {
        if (! empty($returnAttributes) && ! empty($returnDocs)) {
            $returnDocs[] = $returnAttributes;
        }

        if (! empty($returnDocs)) {
            $values = $builder->aqb->merge(...$returnDocs);
        }

        return $values;
    }

    protected function mergeJoinResults($builder, $baseTable)
    {
        $tablesToJoin = [];
        foreach ($builder->joins as $key => $join) {
            $tableAlias = $this->getTableAlias($join->table);

            if (! isset($tableAlias)) {
                $tableAlias = $this->generateTableAlias($join->table);
            }
            $tablesToJoin[$key] = $tableAlias;
        }

        $tablesToJoin = array_reverse($tablesToJoin);
        $tablesToJoin[] = $baseTable;

        return $builder->aqb->merge(...$tablesToJoin);
    }
}
