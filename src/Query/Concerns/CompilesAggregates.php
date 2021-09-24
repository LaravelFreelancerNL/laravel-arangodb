<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesAggregates
{
    /**
     * Compile an aggregated select clause.
     *
     * @param Builder $builder
     * @param array   $aggregate
     *
     * @return Builder
     */
    protected function compileAggregate(Builder $builder, $aggregate)
    {
        $method = 'compile' . ucfirst($aggregate['function']);

        return $this->$method($builder, $aggregate);
    }

    /**
     * Compile AQL for count aggregate.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    protected function compileCount(Builder $builder)
    {
        $builder->aqb = $builder->aqb->collect()->withCount('aggregateResult');

        return $builder;
    }

    /**
     * Compile AQL for max aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return Builder
     */
    protected function compileMax(Builder $builder, $aggregate)
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->max($column));

        return $builder;
    }

    /**
     * Compile AQL for min aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return Builder
     */
    protected function compileMin(Builder $builder, $aggregate)
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->min($column));

        return $builder;
    }

    /**
     * Compile AQL for average aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return Builder
     */
    protected function compileAvg(Builder $builder, $aggregate)
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->average($column));

        return $builder;
    }

    /**
     * Compile AQL for sum aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return Builder
     */
    protected function compileSum(Builder $builder, $aggregate)
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->sum($column));

        return $builder;
    }
}
