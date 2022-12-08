<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

trait CompilesJoins
{
    /**
     * Compile the "join" portions of the query.
     *
     * @param  Builder  $builder
     * @param  array  $joins
     * @return string
     */
    protected function compileJoins(Builder $builder, $joins)
    {
        foreach ($joins as $join) {
            $compileMethod = 'compile'.ucfirst($join->type).'Join';
            $builder = $this->$compileMethod($builder, $join);
        }

        return $builder;
    }

    protected function compileInnerJoin(Builder $builder, $join)
    {
        $table = $join->table;
        $alias = $this->generateTableAlias($table);
        $this->registerTableAlias($table, $alias);
        $builder->aqb = $builder->aqb->for($alias, $table)
            ->filter($this->compileWheresToArray($join));

        return $builder;
    }

    protected function compileLeftJoin(Builder $builder, $join)
    {
        $table = $join->table;
        $alias = $this->generateTableAlias($table);
        $this->registerTableAlias($table, $alias);

        $resultsToJoin = (new QueryBuilder())
            ->for($alias, $table)
            ->filter($this->compileWheresToArray($join))
            ->return($alias);

        $builder->aqb = $builder->aqb->let($table, $resultsToJoin)
            ->for(
                $alias,
                $builder->aqb->if(
                    [$builder->aqb->length($table), '>', 0],
                    $table,
                    '[]'
                )
            );

        return $builder;
    }

    protected function compileCrossJoin(Builder $builder, $join)
    {
        $table = $join->table;
        $alias = $this->generateTableAlias($table);
        $builder->aqb = $builder->aqb->for($alias, $table);

        return $builder;
    }
}
