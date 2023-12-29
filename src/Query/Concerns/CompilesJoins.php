<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesJoins
{
    /**
     * @param $join
     * @param Builder $query
     * @return array
     */
    public function extractTableAndAlias(Builder $query, $join): array
    {
        if ($join->table instanceof Expression) {
            $tableParts = [];
            preg_match("/(^.*) as (.*?)$/", $join->table->getValue($query->grammar), $tableParts);
            $table = $tableParts[1];
            $alias = $tableParts[2];

            $query->registerTableAlias($join->table, $alias);

            return [$table, $alias];
        }

        $table = $this->wrapTable($join->table);
        $alias = $query->generateTableAlias($join->table);
        $query->registerTableAlias($join->table, $alias);

        return [$table, $alias];
    }

    /**
     * Compile the "join" portions of the query.
     *
     * @param IlluminateQueryBuilder $query
     * @param  array  $joins
     * @return string
     */
    protected function compileJoins(IlluminateQueryBuilder $query, $joins)
    {
        return collect($joins)->map(function ($join) use ($query) {
            return match ($join->type) {
                'cross' => $this->compileCrossJoin($query, $join),
                'left' => $this->compileLeftJoin($query, $join),
                default => $this->compileInnerJoin($query, $join),
            };
        })->implode(' ');
    }

    protected function compileCrossJoin(IlluminateQueryBuilder $query, $join)
    {
        assert($query instanceof Builder);

        $table = $this->wrapTable($join->table);
        $alias = $query->generateTableAlias($join->table);
        $query->registerTableAlias($join->table, $alias);

        return 'FOR ' . $alias . ' IN ' . $table;
    }

    protected function compileInnerJoin(IlluminateQueryBuilder $query, $join)
    {
        assert($query instanceof Builder);

        list($table, $alias) = $this->extractTableAndAlias($query, $join);

        $filter = $this->compileWheres($join);

        $aql = 'FOR ' . $alias . ' IN ' . $table;
        if ($filter) {
            $aql .= ' ' . $filter;
        }

        return $aql;
    }

    protected function compileLeftJoin(IlluminateQueryBuilder $query, $join)
    {
        assert($query instanceof Builder);

        list($table, $alias) = $this->extractTableAndAlias($query, $join);

        $filter = $this->compileWheres($join);

        $resultsToJoin = 'FOR ' . $alias . ' IN ' . $table;
        if ($filter) {
            $resultsToJoin .= ' ' . $filter;
        }
        $resultsToJoin .= ' RETURN ' . $alias;

        $aql = 'LET ' . $alias . 'List = (' . $resultsToJoin . ')';
        $aql .= ' FOR ' . $alias . ' IN (LENGTH(' . $alias . 'List) > 0) ? ' . $alias . 'List : [{}]';

        return $aql;
    }
}
