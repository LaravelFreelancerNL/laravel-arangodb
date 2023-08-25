<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

trait CompilesJoins
{
    /**
     * Compile the "join" portions of the query.
     *
     * @param  array<mixed>  $joins
     */
    //    protected function compileJoins(IlluminateBuilder $query, $joins): string
    //    {
    //        foreach ($joins as $join) {
    //            $compileMethod = 'compile'.ucfirst($join->type).'Join';
    //            $query = $this->$compileMethod($query, $join);
    //        }
    //
    //        return $query;
    //    }

    //    protected function compileInnerJoin(Builder $query, $join)
    //    {
    //        $table = $join->table;
    //        $alias = $this->generateTableAlias($table);
    //        $this->registerTableAlias($table, $alias);
    //        $query->aqb = $query->aqb->for($alias, $table)
    //            ->filter($this->compileWheresToArray($join));
    //
    //        return $query;
    //    }

    //    protected function compileLeftJoin(Builder $query, $join)
    //    {
    //        $table = $join->table;
    //        $alias = $this->generateTableAlias($table);
    //        $this->registerTableAlias($table, $alias);
    //
    //        $resultsToJoin = (new QueryBuilder())
    //            ->for($alias, $table)
    //            ->filter($this->compileWheresToArray($join))
    //            ->return($alias);
    //
    //        $query->aqb = $query->aqb->let($table, $resultsToJoin)
    //            ->for(
    //                $alias,
    //                $query->aqb->if(
    //                    [$query->aqb->length($table), '>', 0],
    //                    $table,
    //                    '[]'
    //                )
    //            );
    //
    //        return $query;
    //    }

    //    protected function compileCrossJoin(Builder $query, $join)
    //    {
    //        $table = $join->table;
    //        $alias = $this->generateTableAlias($table);
    //        $query->aqb = $query->aqb->for($alias, $table);
    //
    //        return $query;
    //    }
}
