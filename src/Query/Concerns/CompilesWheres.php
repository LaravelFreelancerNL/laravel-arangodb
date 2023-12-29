<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesWheres
{
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
     * Determine if the given value is a raw expression.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isExpression($value)
    {
        return $value instanceof Expression;
    }

    protected function normalizeOperator($where)
    {
        if (isset($where['operator'])) {
            $where['operator'] = $this->translateOperator($where['operator']);
        }
        if (!isset($where['operator'])) {
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
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBasic(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        if (! $where['column'] instanceof expression) {
            $column = $this->normalizeColumn($query, $where['column']);
        }
        if ($where['column'] instanceof expression) {
            $column = $where['column']->getValue($this);
        }

        $predicate[0] = $column;
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($where['value']);

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
    protected function whereBetween(IlluminateQueryBuilder $query, $where)
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

        return implode(" ", $predicate[0]) . " " . $boolean . " " . implode(" ", $predicate[1]);
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
     * @param IlluminateQueryBuilder $query
     * @param array $where
     * @return string
     * @throws \Exception
     */
    protected function whereBetweenColumns(IlluminateQueryBuilder $query, $where)
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

        return implode(" ", $predicate[0]) . " " . $boolean . " " . implode(" ", $predicate[1]);

    }

    /**
     * Compile a where clause comparing two columns..
     *
     * @param IlluminateQueryBuilder $query
     * @param array $where
     * @return string
     * @throws \Exception
     */
    protected function whereColumn(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);
        $predicate[0] = $this->normalizeColumn($query, $where['first']);
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->normalizeColumn($query, $where['second']);

        return implode(" ", $predicate);
    }

    /**
     * Compile a "where null" clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $where
     * @return string
     * @throws \Exception
     */
    protected function whereNull(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = '==';
        $predicate[2] = 'null';

        return implode(" ", $predicate);
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotNull(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = '!=';
        $predicate[2] = 'null';

        return implode(' ', $predicate);
    }

    /**
     * Compile a "where in" clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereIn(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'IN';
        $predicate[2] = $this->parameter($where['values']);

        return implode(" ", $predicate);
    }

    /**
     * Compile a "where in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereInRaw(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'IN';
        $predicate[2] = '[' . implode(', ', $where['values']) . ']';

        return implode(" ", $predicate);
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotIn(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'NOT IN';
        $predicate[2] = $this->parameter($where['values']);

        return implode(" ", $predicate);
    }

    /**
     * Compile a "where not in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotInRaw(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'NOT IN';
        $predicate[2] = '[' . implode(', ', $where['values']) . ']';

        return implode(" ", $predicate);
    }

    /**
     * Compile a "where JSON contains" clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param  array  $where
     * @return string
     */
    protected function whereJsonContains(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $operator = $where['not'] ? 'NOT IN' : 'IN';

        //FIXME: bind value earlier
        $predicate[0] = $this->parameter($where['value']);
        $predicate[1] = $operator;
        $predicate[2] = $this->normalizeColumn($query, $where['column']);

        return implode(" ", $predicate);
    }

    /**
     * Compile a "whereJsonContains" clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereJsonLength(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $column = $this->normalizeColumn($query, $where['column']);

        $predicate[0] = "LENGTH($column)";
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($where['value']);

        return implode(" ", $predicate);
    }

    /**
     * Compile a where date clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $where
     * @return string
     * @throws \Exception
     */
    protected function whereDate(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);
        $predicate[0] = 'DATE_FORMAT(' . $this->normalizeColumn($query, $where['column']) . ', "%yyyy-%mm-%dd")';
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($where['value']);

        return implode(' ', $predicate);
    }

    /**
     * Compile a where year clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $where
     * @return string
     * @throws \Exception
     */
    protected function whereYear(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = 'DATE_YEAR(' . $this->normalizeColumn($query, $where['column']) . ')';
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($where['value']);

        return implode(' ', $predicate);
    }

    /**
     * Compile a where month clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $where
     * @return string
     * @throws \Exception
     */
    protected function whereMonth(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] =  'DATE_MONTH(' . $this->normalizeColumn($query, $where['column']) . ')';
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($where['value']);

        return implode(' ', $predicate);
    }


    /**
     * Compile a where day clause.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $where
     * @return string
     * @throws \Exception
     */
    protected function whereDay(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = 'DATE_DAY(' . $this->normalizeColumn($query, $where['column']) . ')';
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($where['value']);

        return implode(' ', $predicate);
    }

    /**
     * Compile a where time clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereTime(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = 'DATE_FORMAT(' . $this->normalizeColumn($query, $where['column']) . ", '%hh:%ii:%ss')";
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($where['value']);

        return implode(' ', $predicate);
    }

    /**
     * Compile a nested where clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    //    protected function whereNested(Builder $query, $where)
    //    {
    //        $predicates = [];
    //        $predicates = $this->compileWheresToArray($where['query']);
    //
    //        $query->aqb->binds = array_merge($query->aqb->binds, $where['query']->aqb->binds);
    //        $query->aqb->collections = array_merge_recursive($query->aqb->collections, $where['query']->aqb->collections);
    //
    //        return $predicates;
    //    }

    /**
     * Compile a where condition with a sub-select.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $where
     * @return string
     * @throws \Exception
     */
    protected function whereSub(IlluminateQueryBuilder $query, $where)
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = $where['operator'];
        $predicate[2] = $where['subquery'];

        return implode(' ', $predicate);
    }

    /**
     * Compile a where exists clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function whereExists(IlluminateQueryBuilder $query, $where)
    {
        return 'LENGTH(' . $where['subquery'] . ') > 0';
    }

    /**
     * Compile a where exists clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function whereNotExists(IlluminateQueryBuilder $query, $where)
    {
        return 'LENGTH(' . $where['subquery'] . ') == 0';
    }
}
