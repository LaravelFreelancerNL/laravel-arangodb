<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesUnions
{
    /**
     * Compile the "union" queries attached to the main query.
     *
     * @param Builder $query
     * @param string $aql
     * @return string
     */
    protected function compileUnions(IlluminateBuilder $query, $firstQuery = '')
    {
        $unionResultsId = 'union'.$query->getQueryId().'Results';
        $unionDocId = 'union'.$query->getQueryId().'Results';

        $firstQuery = $this->wrapUnion($firstQuery);
        $unions = '';
        foreach ($query->unions as $union) {
            $prefix = ($unions !== '') ? $unions : $firstQuery;
            $unions = $this->compileUnion($union, $prefix);
        }

        $aql = 'LET '.$unionResultsId.' = '.$unions
            .' FOR '.$unionDocId.'Doc IN '.$unionResultsId;
        $aql .= ' RETURN '.$unionDocId.'Doc';

//        if (! empty($query->unionOrders)) {
//            $sql .= ' '.$this->compileOrders($query, $query->unionOrders);
//        }
//
//        if (isset($query->unionLimit)) {
//            $sql .= ' '.$this->compileLimit($query, $query->unionLimit);
//        }
//
//        if (isset($query->unionOffset)) {
//            $sql .= ' '.$this->compileOffset($query, $query->unionOffset);
//        }

        return $aql;
    }

    /**
     * Compile a single union statement.
     *
     * @param  array  $union
     * @return string
     */
    protected function compileUnion(array $union, string $aql = '')
    {
        $unionType = $union['all'] ? 'UNION' : 'UNION_DISTINCT';

        return $unionType.'('.$aql.', '.$this->wrapUnion($union['query']->toSql()).')';
    }

    /**
     * Compile the "union" queries attached to the main query.
     *
     * @param IlluminateBuilder $query
     * @param string $aql
     * @return string
     */
    protected function compileUnionsX(IlluminateBuilder $query, $aql = '')
    {
        $unionType = $query->unions[0]['all'] ? 'UNION' : 'UNION_DISTINCT';

        $unions = [];


        foreach ($query->unions as $union) {
            ray('compileUnions', $union['query']);
            $unions[] = $this->wrapUnion($union['query']->toSql());
        }
        $unions[] = $this->wrapUnion($aql);


//        if (!empty($query->unionOrders)) {
//            $sql .= ' ' . $this->compileOrders($query, $query->unionOrders);
//        }
//
//        if (isset($query->unionLimit)) {
//            $sql .= ' ' . $this->compileLimit($query, $query->unionLimit);
//        }
//
//        if (isset($query->unionOffset)) {
//            $sql .= ' ' . $this->compileOffset($query, $query->unionOffset);
//        }
//
//        return ltrim($sql);
        return $aql;
    }
}