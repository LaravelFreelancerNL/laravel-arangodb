<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesColumns
{
    /**
     * Compile the "RETURN" portion of the query.
     *
     * @param Builder $builder
     * @param array   $columns
     *
     * @return Builder
     */
    protected function compileColumns(Builder $builder, array $columns): Builder
    {
        $returnDocs = [];
        $returnAttributes = [];
        $values = [];

        // Prepare columns
        foreach ($columns as $column) {
            // Extract rows
            if (is_string($column) && str_ends_with($column, '.*')) {
                $table = substr($column, 0, strlen($column) - 2);
                $returnDocs[] = $this->getTableAlias($table);

                continue;
            }

            // Extract groups
            if (is_array($builder->groups) && in_array($column, $builder->groups)) {
                $returnAttributes[] = $column;

                continue;
            }

            if ($column != null && $column != '*') {
                [$column, $alias] = $this->extractAlias($column);

                $returnAttributes[$alias] = $this->normalizeColumn($builder, $column);
            }
        }
        $values = $this->determineReturnValues($builder, $returnAttributes, $returnDocs);

        $builder->aqb = $builder->aqb->return($values, (bool) $builder->distinct);

        return $builder;
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
            if (is_array($builder->joins) && !empty($builder->joins)) {
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
