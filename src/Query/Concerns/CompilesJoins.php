<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesJoins
{
    //    /**
    //     * Compile the "join" portions of the query.
    //     *
    //     * @param  \Illuminate\Database\Query\Builder  $query
    //     * @param  array  $joins
    //     * @return string
    //     */
    //    protected function compileJoins(IlluminateBuilder $query, $joins)
    //    {
    //        foreach ($joins as $join) {
    //            $compileMethod = 'compile'.ucfirst($join->type).'Join';
    //            $query = $this->$compileMethod($query, $join);
    //        }
    //
    //        return $query;
    //    }

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
            //            $table = $this->wrapTable($join->table);
            //
            //            $nestedJoins = is_null($join->joins) ? '' : ' '.$this->compileJoins($query, $join->joins);
            //
            //            $tableAndNestedJoins = is_null($join->joins) ? $table : '('.$table.$nestedJoins.')';
            //
            //            return trim("{$join->type} join {$tableAndNestedJoins} {$this->compileWheres($join)}");
        })->implode(' ');
    }

    protected function compileInnerJoin(Builder $query, $join)
    {
        $table = $this->wrapTable($join->table);
        $alias = $this->generateTableAlias($join->table);
        $this->registerTableAlias($join->table, $alias);

        $filter = $this->compileWheres($join);

        $aql = 'FOR '.$alias.' IN '.$table;
        if ($filter) {
            $aql .= ' '.$filter;
        }

        return $aql;
    }

    protected function compileLeftJoin(IlluminateBuilder $query, $join)
    {
        $table = $this->wrapTable($join->table);
        $alias = $this->generateTableAlias($join->table);
        $this->registerTableAlias($join->table, $alias);

        $filter = $this->compileWheres($join);

        $resultsToJoin = 'FOR '.$alias.' IN '.$table;
        if ($filter) {
            $resultsToJoin .= ' '.$filter;
        }
        $resultsToJoin .= ' RETURN '.$alias;

        $aql = 'LET '.$table.' = ('.$resultsToJoin.')';
        $aql .= ' FOR '.$alias.' IN (LENGTH('.$join->table.') > 0) ? '.$table.' : []';

        return $aql;
    }

    protected function compileCrossJoin(Builder $query, $join)
    {
        $table = $this->wrapTable($join->table);
        $alias = $this->generateTableAlias($join->table);
        $this->registerTableAlias($join->table, $alias);

        return 'FOR '.$alias.' IN '.$table;
    }
}
