<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesUnions
{
    /**
     * Compile the "union" queries attached to the main query.
     *
     * @param Builder $query
     * @param string $firstQuery
     * @return string
     */
    protected function compileUnions(IlluminateBuilder $query, $firstQuery = '')
    {
        if (!is_array($query->unions)) {
            return '';
        }

        $unionResultsId = 'laravel_union_' . $query->getQueryId() . '_results';
        $unionDocId = 'laravel_union_' . $query->getQueryId() . '_result';

        $query->registerTableAlias($unionResultsId, $unionDocId);

        $firstQuery = $this->wrapSubquery($firstQuery);

        $unions = '';

        foreach ($query->unions as $union) {
            $prefix = ($unions !== '') ? $unions : $firstQuery;
            $unions = $this->compileUnion($union, $prefix);
        }

        $aql = 'LET ' . $unionResultsId . ' = ' . $unions
            . ' FOR ' . $unionDocId . ' IN ' . $unionResultsId;

        if (!empty($query->unionOrders)) {
            $aql .= ' ' . $this->compileOrders($query, $query->unionOrders, $unionResultsId);
        }

        if ($query->unionOffset) {
            $aql .= ' ' . $this->compileOffset($query, $query->unionOffset);
        }

        if ($query->unionLimit) {
            $aql .= ' ' . $this->compileLimit($query, $query->unionLimit);
        }

        if ($query->aggregate !== null) {
            $originalFrom = $query->from;
            $query->from = $unionResultsId;

            $aql .= ' ' . $this->compileAggregate($query, $query->aggregate);

            $query->from = $originalFrom;

            return $aql . ' RETURN { `aggregate`: aggregateResult }';
        }

        return $aql . ' RETURN ' . $unionDocId;
    }

    /**
     * Compile a single union statement.
     *
     * @param array<mixed> $union
     * @param string $aql
     * @return string
     */
    protected function compileUnion(array $union, string $aql = '')
    {
        $unionType = $union['all'] ? 'UNION' : 'UNION_DISTINCT';

        return $unionType . '(' . $aql . ', ' . $this->wrapSubquery($union['query']->toSql()) . ')';
    }
}
