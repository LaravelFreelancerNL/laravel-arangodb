<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesJoins
{
    /**
     * Compile the "join" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $joins
     * @return string
     */
    protected function compileJoins(IlluminateBuilder $query, $joins)
    {
        return collect($joins)->map(function ($join) use ($query) {
            return match ($join->type) {
                'cross' => $this->compileCrossJoin($query, $join),
                'inner' => $this->compileInnerJoin($query, $join),
                'left' => $this->compileLeftJoin($query, $join),
            };
        })->implode(' ');
    }

    protected function compileCrossJoin(Builder $query, $join)
    {
        $table = $this->wrapTable($join->table);
        $alias = $query->generateTableAlias($join->table);
        $query->registerTableAlias($join->table, $alias);

        return 'FOR ' . $alias . ' IN ' . $table;
    }

    protected function compileInnerJoin(Builder $query, $join)
    {
        if ($join->table instanceof Expression) {
            $tableParts = [];
            preg_match("/(^.*) as (.*?)$/", $join->table->getValue($query->grammar), $tableParts);
            $table = $tableParts[1];
            $alias = $tableParts[2];

            $query->registerTableAlias($join->table, $alias);
        } else {
            $table = $this->wrapTable($join->table);
            $alias = $query->generateTableAlias($join->table);
            $query->registerTableAlias($join->table, $alias);
        }

        $filter = $this->compileWheres($join);

        $aql = 'FOR ' . $alias . ' IN ' . $table;
        if ($filter) {
            $aql .= ' ' . $filter;
        }

        return $aql;
    }

    protected function compileLeftJoin(IlluminateBuilder $query, $join)
    {
        //        $table = $this->wrapTable($join->table);
        //        $alias = $this->generateTableAlias($join->table);
        //        $query->registerTableAlias($join->table, $alias);
        if ($join->table instanceof Expression) {
            $tableParts = [];
            preg_match("/(^.*) as (.*?)$/", $join->table->getValue($query->grammar), $tableParts);
            $table = $tableParts[1];
            $alias = $tableParts[2];

            $query->registerTableAlias($join->table, $alias);
        } else {
            $table = $this->wrapTable($join->table);
            $alias = $query->generateTableAlias($join->table);
            $query->registerTableAlias($join->table, $alias);
        }

        $filter = $this->compileWheres($join);

        $resultsToJoin = 'FOR ' . $alias . ' IN ' . $table;
        if ($filter) {
            $resultsToJoin .= ' ' . $filter;
        }
        $resultsToJoin .= ' RETURN ' . $alias;

        $aql = 'LET ' . $table . ' = (' . $resultsToJoin . ')';
        $aql .= ' FOR ' . $alias . ' IN (LENGTH(' . $table . ') > 0) ? ' . $table . ' : []';

        return $aql;
    }
}
