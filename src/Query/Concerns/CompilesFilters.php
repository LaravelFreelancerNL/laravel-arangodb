<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesFilters
{
    /**
     * Compile the "having" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileHavings(IlluminateQueryBuilder $query)
    {
        return 'FILTER ' . $this->removeLeadingBoolean(collect($query->havings)->map(function ($having) use ($query) {
            return $having['boolean'] . ' ' . $this->filter($query, $having);
        })->implode(' '));
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
            return $where['boolean'] . ' ' . $this->{"filter{$where['type']}"}($query, $where);
        })->all();
    }

    /**
     * Format the where clause statements into one string.
     *
     * @param IlluminateQueryBuilder $query
     * @param  array  $sql
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function concatenateWhereClauses($query, $sql)
    {
        return 'FILTER ' . $this->removeLeadingBoolean(implode(' ', $sql));
    }

    protected function getOperatorByWhereType($type)
    {
        if (isset($this->whereTypeOperators[$type])) {
            return $this->whereTypeOperators[$type];
        }

        return '==';
    }


    /**
     * Compile a single having clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filter(IlluminateQueryBuilder $query, array $filter)
    {
        // If the having clause is "raw", we can just return the clause straight away
        // without doing any more processing on it. Otherwise, we will compile the
        // clause into SQL based on the components that make it up from builder.
        return match ($filter['type']) {
            'Raw' => $filter['sql'],
            'between' => $this->filterBetween($query, $filter),
            'Null' => $this->filterNull($query, $filter),
            'NotNull' => $this->filterNotNull($query, $filter),
            'bit' => $this->filterBit($query, $filter),
            'Expression' => $this->filterExpression($filter),
            'Nested' => $this->compileNestedHavings($filter),
            default => $this->filterBasic($query, $filter),
        };
    }

    /**
     * Compile a basic having|filter clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filterBasic(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);

        if (!$filter['column'] instanceof expression) {
            $column = $this->normalizeColumn($query, $filter['column']);
        }
        if ($filter['column'] instanceof expression) {
            $column = $filter['column']->getValue($this);
        }

        $predicate[0] = $column;
        $predicate[1] = $filter['operator'];
        $predicate[2] = $this->parameter($filter['value']);

        return implode(" ", $predicate);
    }


    /**
     * Compile a "between" filter clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param $filter
     * @return string
     * @throws \Exception
     */
    protected function filterBetween(IlluminateQueryBuilder $query, $filter): string
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
     * Compile a "between columns" filter clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filterBetweenColumns(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        [$minOperator, $maxOperator, $boolean] = $this->getBetweenOperators($filter['not']);

        $column = $this->normalizeColumn($query, $filter['column']);

        $predicate[0][0] = $column;
        $predicate[0][1] = $minOperator;
        $predicate[0][2] = $this->normalizeColumn($query, reset($filter['values']));

        $predicate[1][0] = $column;
        $predicate[1][1] = $maxOperator;
        $predicate[1][2] = $this->normalizeColumn($query, end($filter['values']));

        return implode(" ", $predicate[0]) . " " . $boolean . " " . implode(" ", $predicate[1]);
    }

    /**
     * Compile a filter clause comparing two columns..
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filterColumn(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);
        $predicate[0] = $this->normalizeColumn($query, $filter['first']);
        $predicate[1] = $filter['operator'];
        $predicate[2] = $this->normalizeColumn($query, $filter['second']);

        return implode(" ", $predicate);
    }

    /**
     * Compile a "filter null" clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param $filter
     * @return string
     * @throws \Exception
     */
    protected function filterNull(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = '==';
        $predicate[2] = "null";

        return implode(" ", $predicate);
    }

    /**
     * Compile a "filter not null" clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param $filter
     * @return string
     * @throws \Exception
     */
    protected function filterNotNull(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = '!=';
        $predicate[2] = "null";

        return implode(" ", $predicate);
    }

    /**
     * Compile a "filter in" clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $filter
     * @return string
     */
    protected function filterIn(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = 'IN';
        $predicate[2] = $this->parameter($filter['values']);

        return implode(" ", $predicate);
    }

    /**
     * Compile a "filter in raw" clause.
     *
     * For safety, filterIntegerInRaw ensures this method is only used with integer values.
     *
     * @param IlluminateQueryBuilder $query
     * @param array<mixed> $filter
     * @return string
     * @throws \Exception
     */
    protected function filterInRaw(IlluminateQueryBuilder $query, array $filter): string
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = 'IN';
        $predicate[2] = '[' . implode(', ', $filter['values']) . ']';

        return implode(" ", $predicate);
    }

    /**
     * Compile a "filter not in" clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array<mixed> $filter
     * @return string
     * @throws \Exception
     */
    protected function filterNotIn(IlluminateQueryBuilder $query, array $filter): string
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = 'NOT IN';
        $predicate[2] = $this->parameter($filter['values']);

        return implode(" ", $predicate);
    }

    /**
     * Compile a "filter not in raw" clause.
     *
     * For safety, filterIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $filter
     * @return string
     */
    protected function filterNotInRaw(IlluminateQueryBuilder $query, array $filter): string
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = 'NOT IN';
        $predicate[2] = '[' . implode(', ', $filter['values']) . ']';

        return implode(" ", $predicate);
    }

    /**
     * Compile a "filter JSON contains" clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array<mixed> $filter
     * @return string
     * @throws \Exception
     */
    protected function filterJsonContains(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $operator = $filter['not'] ? 'NOT IN' : 'IN';

        $predicate[0] = $this->parameter($filter['value']);
        $predicate[1] = $operator;
        $predicate[2] = $this->normalizeColumn($query, $filter['column']);

        return implode(" ", $predicate);
    }

    /**
     * Compile a "filterJsonLength" clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array<mixed>  $filter
     * @return string
     */
    protected function filterJsonLength(IlluminateQueryBuilder $query, array $filter): string
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);

        $column = $this->normalizeColumn($query, $filter['column']);

        $predicate[0] = "LENGTH($column)";
        $predicate[1] = $filter['operator'];
        $predicate[2] = $this->parameter($filter['value']);

        return implode(" ", $predicate);
    }

    /**
     * @param array $filter
     * @return mixed
     */
    protected function filterExpression($filter)
    {
        return $filter['column']->getValue($this);
    }

    /**
     * Compile a filter date clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filterDate(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);
        $predicate[0] = 'DATE_FORMAT(' . $this->normalizeColumn($query, $filter['column']) . ', "%yyyy-%mm-%dd")';
        $predicate[1] = $filter['operator'];
        $predicate[2] = $this->parameter($filter['value']);

        return implode(' ', $predicate);
    }

    /**
     * Compile a filter year clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filterYear(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);

        $predicate[0] = 'DATE_YEAR(' . $this->normalizeColumn($query, $filter['column']) . ')';
        $predicate[1] = $filter['operator'];
        $predicate[2] = $this->parameter($filter['value']);

        return implode(' ', $predicate);
    }

    /**
     * Compile a filter month clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filterMonth(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);

        $predicate[0] =  'DATE_MONTH(' . $this->normalizeColumn($query, $filter['column']) . ')';
        $predicate[1] = $filter['operator'];
        $predicate[2] = $this->parameter($filter['value']);

        return implode(' ', $predicate);
    }


    /**
     * Compile a filter day clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filterDay(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);

        $predicate[0] = 'DATE_DAY(' . $this->normalizeColumn($query, $filter['column']) . ')';
        $predicate[1] = $filter['operator'];
        $predicate[2] = $this->parameter($filter['value']);

        return implode(' ', $predicate);
    }

    /**
     * Compile a filter time clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $filter
     * @return string
     */
    protected function filterTime(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);

        $predicate[0] = 'DATE_FORMAT(' . $this->normalizeColumn($query, $filter['column']) . ", '%hh:%ii:%ss')";
        $predicate[1] = $filter['operator'];
        $predicate[2] = $this->parameter($filter['value']);

        return implode(' ', $predicate);
    }

    /**
     * Compile a filter condition with a sub-select.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filterSub(IlluminateQueryBuilder $query, $filter)
    {
        $predicate = [];

        $filter = $this->normalizeOperator($filter);

        $predicate[0] = $this->normalizeColumn($query, $filter['column']);
        $predicate[1] = $filter['operator'];
        $predicate[2] = $filter['subquery'];

        return implode(' ', $predicate);
    }

    /**
     * Compile a filter exists clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $filter
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function filterExists(IlluminateQueryBuilder $query, $filter)
    {
        return 'LENGTH(' . $filter['subquery'] . ') > 0';
    }

    /**
     * Compile a filter exists clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $filter
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function filterNotExists(IlluminateQueryBuilder $query, $filter)
    {
        return 'LENGTH(' . $filter['subquery'] . ') == 0';
    }

    /**
     * Compile a nested where clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function filterNested(Builder $query, $where)
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query we need to take the leading "where" of queries.
        $offset = $where['query'] instanceof JoinClause ? 3 : 6;

        return '(' . substr($this->compileWheres($where['query']), $offset) . ')';
    }


    /**
     * Compile a having clause involving a bit operator.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $filter
     * @return string
     * @throws \Exception
     */
    protected function filterBit(IlluminateQueryBuilder $query, $filter)
    {
        $column = $this->normalizeColumn($query, $filter['column']);
        $value = $this->parameter($filter['value']);

        $bitExpression = match ($filter['operator']) {
            '|' => $this->bitExpressionOr($column, $value),
            '^' => $this->bitExpressionXor($column, $value),
            '~' => $this->bitExpressionNegate($column),
            '&~' => $this->bitExpressionNegatedAnd($column, $value),
            '<<' => $this->bitExpressionLeftShift($column, $value),
            '>>' => $this->bitExpressionRightShift($column, $value),
            default =>  $this->bitExpressionAnd($column, $value),
        };

        return '(' . $bitExpression . ') != 0';
    }

    protected function bitExpressionAnd(string $column, int|string $value): string
    {
        return 'BIT_AND(' . $column . ', ' . $value . ')';
    }

    protected function bitExpressionOr(string $column, int|string $value): string
    {
        return 'BIT_AND(' . $column . ', ' . $value . ')';
    }

    protected function bitExpressionXor(string $column, int|string $value): string
    {
        return 'BIT_XOR(' . $column . ', ' . $value . ')';
    }

    protected function bitExpressionNegate(string $column): string
    {
        return 'BIT_NEGATE(' . $column . ', BIT_POPCOUNT(' . $column . '))';
    }

    protected function bitExpressionNegatedAnd(string $column, int|string $value): string
    {
        return 'BIT_AND(' . $column . ', BIT_NEGATE(' . $value . ', BIT_POPCOUNT(' . $value . '))';
    }

    protected function bitExpressionLeftShift(string $column, int|string $value): string
    {
        return 'BIT_SHIFT_LEFT(' . $column . ',  ' . $value . ', BIT_POPCOUNT(' . $column . '))';
    }

    protected function bitExpressionRightShift(string $column, int|string $value): string
    {
        return 'BIT_SHIFT_RIGHT(' . $column . ',  ' . $value . ', BIT_POPCOUNT(' . $column . '))';
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

    protected function normalizeOperator($filter)
    {
        if (isset($filter['operator'])) {
            $filter['operator'] = $this->translateOperator($filter['operator']);
        }
        if (!isset($filter['operator'])) {
            $filter['operator'] = $this->getOperatorByWhereType($filter['type']);
        }

        return $filter;
    }
}
