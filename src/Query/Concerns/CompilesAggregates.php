<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesAggregates
{
    /**
     * Compile an aggregated select clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $aggregate
     * @return string
     */
    protected function compileAggregate(IlluminateBuilder $query, $aggregate)
    {
        $method = 'compile' . ucfirst($aggregate['function']);

        return $this->$method($query, $aggregate);
    }

    /**
     * Compile AQL for average aggregate.
     *
     * @param array<mixed> $aggregate
     * @return string
     * @throws \Exception
     */
    protected function compileAvg(Builder $query, array $aggregate)
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        return "COLLECT AGGREGATE aggregateResult = AVERAGE($column)";
    }

    /**
     * Compile AQL for count aggregate.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param Builder $query
     * @return string
     */
    protected function compileCount(Builder $query)
    {
        return "COLLECT WITH COUNT INTO aggregateResult";
    }

    /**
     * Compile an exists statement into AQL.
     *
     * @param  IlluminateBuilder  $query
     * @return string
     */
    public function compileExists(IlluminateBuilder $query)
    {
        $query->columns = [$query->from];

        $select = $this->compileSelect($query);

        return 'RETURN { exists: LENGTH((' . $select . ')) > 0 ? true : false }';
    }


    /**
     * Compile AQL for max aggregate.
     *
     * @param array<mixed> $aggregate
     * @return string
     * @throws \Exception
     */
    protected function compileMax(Builder $query, array $aggregate)
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        return "COLLECT AGGREGATE aggregateResult = MAX($column)";
    }

    /**
     * Compile AQL for min aggregate.
     *
     * @param Builder $query
     * @param array<mixed> $aggregate
     * @return string
     * @throws \Exception
     */
    protected function compileMin(Builder $query, array $aggregate)
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        return "COLLECT AGGREGATE aggregateResult = MIN($column)";
    }

    /**
     * Compile AQL for sum aggregate.
     *
     * @param Builder $query
     * @param array<mixed> $aggregate
     * @return string
     * @throws \Exception
     */
    protected function compileSum(Builder $query, array $aggregate)
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        return "COLLECT AGGREGATE aggregateResult = SUM($column)";
    }
}
