<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IluminateBuilder;
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
     * @param  IluminateBuilder  $query
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
     * @param  IluminateBuilder  $query
     * @param  mixed  $value
     * @return object
     */
    public function parameter(IluminateBuilder $query, $value)
    {
        return $this->isExpression($value) ? $this->getValue($value) : $query->aqb->bind($value);
    }

    protected function normalizeColumn(IluminateBuilder $query, $column)
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
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return array
     */
    protected function whereBasic(IluminateBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($query, $where['value']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "between" where clause.
     *
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBetween(IluminateBuilder $query, $where)
    {
        [$minOperator, $maxOperator, $boolean] = $this->getBetweenOperators($where['not']);

        $min = $this->parameter($query, reset($where['values']));

        $max = $this->parameter($query, end($where['values']));

        $predicate[0][0] = $this->normalizeColumn($query, $where['column']);
        $predicate[0][1] = $minOperator;
        $predicate[0][2] = $min;
        $predicate[0][3] = $where['boolean'];

        $predicate[1][0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1][1] = $maxOperator;
        $predicate[1][2] = $max;
        $predicate[1][3] = $boolean;

        return $predicate;
    }

    /**
     * Generate operators for between and 'not between'
     * @param  bool  $notBetween
     * @return string[]
     */
    protected function getBetweenOperators(bool $notBetween)
    {
        $minOperator = '>=';
        $maxOperator = '<=';
        $boolean = 'AND';

        if ($notBetween) {
            $minOperator = '<';
            $maxOperator = '>';
            $boolean = 'OR';
        }

        return [$minOperator, $maxOperator, $boolean];
    }

    /**
     * Compile a "between" where clause.
     *
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBetweenColumns(IluminateBuilder $query, $where)
    {
        [$minOperator, $maxOperator, $boolean] = $this->getBetweenOperators($where['not']);

        $column = $this->normalizeColumn($query, $where['column']);

        $predicate[0][0] = $column;
        $predicate[0][1] = $minOperator;
        $predicate[0][2] = $this->normalizeColumn($query, reset($where['values']));
        $predicate[0][3] = $where['boolean'];

        $predicate[1][0] = $column;
        $predicate[1][1] = $maxOperator;
        $predicate[1][2] = $this->normalizeColumn($query, end($where['values']));
        $predicate[1][3] = $boolean;

        return $predicate;
    }


    /**
     * Compile a where clause comparing two columns..
     *
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereColumn(IluminateBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $this->normalizeColumn($query, $where['first']);
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->normalizeColumn($query, $where['second']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where null" clause.
     *
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNull(IluminateBuilder $query, $where)
    {
        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = '==';
        $predicate[2] = null;
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotNull(IluminateBuilder $query, $where)
    {
        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = '!=';
        $predicate[2] = null;
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where in" clause.
     *
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereIn(IluminateBuilder $query, $where)
    {
        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'IN';
        $predicate[2] = $this->parameter($query, $where['values']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereInRaw(IluminateBuilder $query, $where)
    {
        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'IN';
        $predicate[2] = '[' . implode(', ', $where['values']) . ']';
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotIn(IluminateBuilder $query, $where)
    {
        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'NOT IN';
        $predicate[2] = $this->parameter($query, $where['values']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where not in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  IluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotInRaw(IluminateBuilder $query, $where)
    {
        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'NOT IN';
        $predicate[2] = '[' . implode(', ', $where['values']) . ']';
        $predicate[3] = $where['boolean'];
        return $predicate;
    }
}