<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesFilterClauses
{
    /**
     * Compile the "having" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileHavings(IlluminateQueryBuilder $query)
    {
        return 'FILTER '.$this->removeLeadingBoolean(collect($query->havings)->map(function ($having) use ($query) {
            return $having['boolean'].' '.$this->compileFilter($query, $having);
        })->implode(' '));
    }

    /**
     * Compile a single having clause.
     *
     * @param array $having
     * @return string
     * @throws \Exception
     */
    protected function compileFilter(IlluminateQueryBuilder $query, array $filter)
    {
        // If the having clause is "raw", we can just return the clause straight away
        // without doing any more processing on it. Otherwise, we will compile the
        // clause into SQL based on the components that make it up from builder.
        return match ($filter['type']) {
            'Raw' => $filter['sql'],
            'between' => $this->compileFilterBetween($query, $filter),
            'Null' => $this->compileFilterNull($query, $filter),
            'NotNull' => $this->compileFilterNotNull($query, $filter),
            'bit' => $this->compileHavingBit($filter),
            'Expression' => $this->compileFilterExpression($filter),
            'Nested' => $this->compileNestedHavings($filter),
            default => $this->compileBasicFilter($query, $filter),
        };
    }

    /**
     * Compile a basic having clause.
     *
     * @param  array  $filter
     * @return string
     */
    protected function compileBasicFilter(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = $filter['operator'];
        $predicate[2] = $this->parameter($filter['value']);

        return implode(" ", $predicate);
    }


    /**
     * Compile a "between" where clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $where
     * @return string
     * @throws \Exception
     */
    protected function compileFilterBetween(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        [$minOperator, $maxOperator, $boolean] = $this->getBetweenOperators($filter['not']);

        $min = $this->parameter(reset($filter['values']));

        $max = $this->parameter(end($filter['values']));

        $normalizedColumn = $this->normalizeColumn($query, $filter['column']);

        $predicate[0][0] = $normalizedColumn;
        $predicate[0][1] = $minOperator;
        $predicate[0][2] = $min;

        $predicate[1][0] = $normalizedColumn;
        $predicate[1][1] = $maxOperator;
        $predicate[1][2] = $max;

        return implode(" ", $predicate[0]) . " " . $boolean . " " . implode(" ", $predicate[1]);
    }

    /**
     * Compile a "where null" clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function compileFilterNull(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = '==';
        $predicate[2] = "null";

        return implode(" ", $predicate);
    }

    /**
     * Compile a "where null" clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function compileFilterNotNull(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = '!=';
        $predicate[2] = "null";

        return implode(" ", $predicate);
    }


    protected function compileFilterExpression($filter)
    {
        return $filter['column']->getValue($this);
    }

    /**
     * Compile a having clause involving a bit operator.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function compileFilterBit(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $column = $this->normalizeColumn($query, $filter['column']);
        $value = $this->parameter($filter['value']);

        $bitExpression = match ($filter['operator']) {
            '&' =>  $this->bitExpressionAnd($column, $value),
            '|' => $this->bitExpressionOr($column, $value),
            '^' => $this->bitExpressionXor($column, $value),
            '~' => $this->bitExpressionNegate($column),
            '&~' => $this->bitExpressionNegatedAnd($column, $value),
            '<<' => $this->bitExpressionLeftShift($column, $value),
            '>>' => $this->bitExpressionRightShift($column, $value)
        };

        return '('.$bitExpression.') != 0';
    }

    protected function bitExpressionAnd(string $column, int|string $value): string
    {
        return 'BIT_AND('.$column.', '.$value.')';
    }

    protected function bitExpressionOr(string $column, int|string $value): string
    {
        return 'BIT_AND('.$column.', '.$value.')';
    }

    protected function bitExpressionXor(string $column, int|string $value): string
    {
        return 'BIT_XOR('.$column.', '.$value.')';
    }

    protected function bitExpressionNegate(string $column): string
    {
        return 'BIT_NEGATE('.$column.', BIT_POPCOUNT('. $column.'))';
    }

    protected function bitExpressionNegatedAnd(string $column, int|string $value): string
    {
        return 'BIT_AND('.$column.', BIT_NEGATE('.$value.', BIT_POPCOUNT('. $value.'))';
    }

    protected function bitExpressionLeftShift(string $column, int|string $value): string
    {
        return 'BIT_SHIFT_LEFT('.$column.',  '.$value.', BIT_POPCOUNT('.$column.'))';
    }

    protected function bitExpressionRightShift(string $column, int|string $value): string
    {
        return 'BIT_SHIFT_RIGHT('.$column.',  '.$value.', BIT_POPCOUNT('.$column.'))';
    }
}
