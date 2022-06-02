<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use Illuminate\Database\Query\JoinClause;

trait CompilesWhereClauses
{
    protected function getOperatorByWhereType($type)
    {
        if (isset($this->whereTypeOperators[$type])) {
            return $this->whereTypeOperators[$type];
        }

        return '==';
    }

    /**
     * Format the where clause statements into one string.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param  IlluminateBuilder  $query
     * @param  array<mixed>  $sql
     */
    protected function concatenateWhereClauses($query, $sql): string
    {
        return 'FILTER ' . $this->removeLeadingBoolean(implode(' ', $sql));
    }

    protected function normalizeOperator($where)
    {
        if (isset($where['operator'])) {
            $where['operator'] = $this->translateOperator($where['operator']);
        }
        if (! isset($where['operator'])) {
            $where['operator'] = $this->getOperatorByWhereType($where['type']);
        }

            return $where;
    }

    /**
     * Translate sql operators to their AQL equivalent where possible.
     *
     * @param string $operator
     *
     * @return mixed|string
     */
    private function translateOperator(string $operator)
    {
        if (isset($this->operatorTranslations[strtolower($operator)])) {
            $operator = $this->operatorTranslations[$operator];
        }

        return $operator;
    }


    /**
     * Compile a basic where clause.
     *
     * @param array<mixed> $where
     * @throws \Exception
     */
    protected function whereBasic(IlluminateBuilder $query, $where): string
    {
        $where = $this->normalizeOperator($where);
        $column = $this->normalizeColumn($query, $where['column']);
        $value = $this->parameter($where['value']);

        $operator = str_replace('?', '??', $where['operator']);

        return $column . ' ' . $operator . ' ' . $value;
    }

    /**
     * Compile a "between" where clause.
     *
     * @param array<mixed> $where
     * @throws \Exception
     */
    protected function whereBetween(IlluminateBuilder $query, $where): string
    {
        $predicate = [];

        [$minOperator, $maxOperator, $boolean] = $this->getBetweenOperators($where['not']);

        $min = $this->parameter(reset($where['values']));

        $max = $this->parameter(end($where['values']));

        $predicate[0][0] = $this->normalizeColumn($query, $where['column']);
        $predicate[0][1] = $minOperator;
        $predicate[0][2] = $min;

        $predicate[1][0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1][1] = $maxOperator;
        $predicate[1][2] = $max;

        return implode(' ', $predicate[0]) . ' and ' . implode(' ', $predicate[1]);
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
     * @param  array<mixed>  $where
     */
    protected function whereBetweenColumns(IlluminateBuilder $query, $where): string
    {
        $predicate = [];

        [$minOperator, $maxOperator, $boolean] = $this->getBetweenOperators($where['not']);

        $column = $this->normalizeColumn($query, $where['column']);

        $predicate[0][0] = $column;
        $predicate[0][1] = $minOperator;
        $predicate[0][2] = $this->normalizeColumn($query, reset($where['values']));

        $predicate[1][0] = $column;
        $predicate[1][1] = $maxOperator;
        $predicate[1][2] = $this->normalizeColumn($query, end($where['values']));

        return implode(' ', $predicate[0]) . ' and ' . implode(' ', $predicate[1]);
    }


    /**
     * Compile a where clause comparing two columns.
     *
     * @param  array<mixed>  $where
     */
    protected function whereColumn(IlluminateBuilder $query, $where): string
    {
        $where = $this->normalizeOperator($where);

        return $this->normalizeColumn($query, $where['first'])
            . ' ' . $where['operator']
            . ' ' . $this->normalizeColumn($query, $where['second']);
    }

    /**
     * Compile a "where null" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereNull(IlluminateBuilder $query, $where): string
    {
        return $this->normalizeColumn($query, $where['column']) . ' == null';
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereNotNull(IlluminateBuilder $query, $where): string
    {
        return $this->normalizeColumn($query, $where['column']) . ' != null';
    }

    /**
     * Compile a "where in" clause.
     *
     * @param array<mixed> $where
     * @throws \Exception
     */
    protected function whereIn(IlluminateBuilder $query, $where): string
    {
        return $this->normalizeColumn($query, $where['column']) . ' IN ' . $this->parameter($where['values']);
    }

    /**
     * Compile a "where in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  array<mixed>  $where
     */
    protected function whereInRaw(IlluminateBuilder $query, $where): string
    {
        return $this->normalizeColumn($query, $where['column']) . ' IN '
            . '[' . implode(', ', $where['values']) . ']';
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereNotIn(IlluminateBuilder $query, $where): string
    {
        return $this->normalizeColumn($query, $where['column'])
            . ' NOT IN ' . $this->parameter($where['values']);
    }

    /**
     * Compile a "where not in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  array<mixed>  $where
     */
    protected function whereNotInRaw(IlluminateBuilder $query, $where): string
    {
        return $this->normalizeColumn($query, $where['column'])
            . ' NOT IN ' . '[' . implode(', ', $where['values']) . ']';
    }

    /**
     * Compile a "where JSON contains" clause.
     *
     * @param array<mixed> $where
     * @throws \Exception
     */
    protected function whereJsonContains(IlluminateBuilder $query, $where): string
    {
        $predicate = [];

        $operator = $where['not'] ? 'NOT IN' : 'IN';

        $predicate[0] = $this->parameter($where['value']);
        $predicate[1] = $operator;
        $predicate[2] = $this->normalizeColumn($query, $where['column']);

        return  implode(' ', $predicate);
    }

    /**
     * Compile a "where JSON length" clause.
     *
     * @param array<mixed> $where
     * @throws \Exception
     */
    protected function whereJsonLength(IlluminateBuilder $query, $where): string
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);
        $column = $this->normalizeColumn($query, $where['column']);

        $predicate[0] = 'LENGTH(' . $column . ')';
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($where['value']);

        return implode(' ', $predicate);
    }


    /**
     * Compile a "where date" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereDate(IlluminateBuilder $query, $where): string
    {
        $where = $this->normalizeOperator($where);

        return 'DATE_FORMAT(' . $this->normalizeColumn($query, $where['column']) . ', "%yyyy-%mm-%dd")'
            . ' ' . $where['operator']
            . ' ' . $this->parameter($where['value']);
    }

    /**
     * Compile a "where year" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereYear(IlluminateBuilder $query, $where): string
    {
        $where = $this->normalizeOperator($where);

        return 'DATE_YEAR(' . $this->normalizeColumn($query, $where['column']) . ')'
            . ' ' . $where['operator']
            . ' ' . $this->parameter($where['value']);
    }

    /**
     * Compile a "where month" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereMonth(IlluminateBuilder $query, $where): string
    {
        $where = $this->normalizeOperator($where);

        return 'DATE_MONTH(' . $this->normalizeColumn($query, $where['column']) . ')'
            . ' ' . $where['operator']
            . ' ' . $this->parameter($where['value']);
    }


    /**
     * Compile a "where day" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereDay(IlluminateBuilder $query, $where): string
    {
        $where = $this->normalizeOperator($where);

        return 'DATE_DAY(' . $this->normalizeColumn($query, $where['column']) . ')'
            . ' ' . $where['operator']
            . ' ' . $this->parameter($where['value']);
    }

    /**
     * Compile a "where time" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereTime(IlluminateBuilder $query, $where): string
    {
        $where = $this->normalizeOperator($where);

        return 'DATE_FORMAT(' . $this->normalizeColumn($query, $where['column']) . ', "%hh:%ii:%ss")'
            . ' ' . $where['operator']
            . ' ' . $this->parameter($where['value']);
    }
    /**
     * Compile a nested where clause.
     *
     * @param  IlluminateBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNested(IlluminateBuilder $query, $where)
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query we need to take the leading "where" of queries.
        $offset = $query instanceof JoinClause ? 3 : 6;

        return '(' . substr($this->compileWheres($where['query']), $offset) . ')';
    }


    /**
     * Compile a where condition with a sub-select.
     *
     * @param  array<mixed>  $where
     */
    protected function whereSub(IlluminateBuilder $query, $where): string
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = $where['operator'];
        $predicate[2] = $where['query']->aqb;
        $predicate[3] = $where['boolean'];

        return $predicate;
    }


    /**
     * Compile a where exists clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereExists(IlluminateBuilder $query, $where): string
    {
        $predicate = [];

        $predicate[0] = $where['query']->aqb;
        $predicate[1] = $where['operator'];
        $predicate[2] = $where['value'];
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a where exists clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereNotExists(IlluminateBuilder $query, $where): string
    {
        $predicate = [];

        $predicate[0] = $where['query']->aqb;
        $predicate[1] = $where['operator'];
        $predicate[2] = $where['value'];
        $predicate[3] = $where['boolean'];

        return $predicate;
    }
}
