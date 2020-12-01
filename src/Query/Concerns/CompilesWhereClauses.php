<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesWhereClauses {
    /**
     * Compile the "where" portions of the query.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    protected function compileWheres(Builder $builder)
    {
        if (is_null($builder->wheres)) {
            return $builder;
        }

        if (count($predicates = $this->compileWheresToArray($builder)) > 0) {
            $builder->aqb = $builder->aqb->filter($predicates);
        }

        return $builder;
    }

    /**
     * Get an array of all the where clauses for the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    protected function compileWheresToArray($query)
    {
        return collect($query->wheres)->map(function ($where) use ($query) {
            return $this->{"where{$where['type']}"}($query, $where);
        })->all();
    }

    protected function getOperatorByWhereType($type)
    {
        if (isset($this->whereTypeOperators[$type])) {
            return $this->whereTypeOperators[$type];
        }

        return '==';
    }

    /**
     * Determine if the given value is a raw expression.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isExpression($value)
    {
        return $value instanceof Expression;
    }

    /**
     * Get the appropriate query parameter place-holder for a value.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  mixed  $value
     * @return object
     */
    public function parameter(\Illuminate\Database\Query\Builder $query, $value)
    {
        return $this->isExpression($value) ? $this->getValue($value) : $query->aqb->bind($value);
    }

    protected function normalizeColumn(\Illuminate\Database\Query\Builder $query, $column)
    {
            if (stripos($column, '.') !== false) {
                return $this->replaceTableForAlias($column);
            }

            return $this->prefixAlias($query->from, $column);
    }

    protected function normalizeOperator($where)
    {
            if (isset($where['operator'])) {
                $where['operator'] = $this->translateOperator($where['operator']);
            } else {
                $where['operator'] = $this->getOperatorByWhereType($where['type']);
            }

            return $where;
    }

    /**
     * Compile a basic where clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return array
     */
    protected function whereBasic(\Illuminate\Database\Query\Builder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($query, $where['value']);
        $predicate[3] = $where['boolean'];


        return $predicate;
    }


}