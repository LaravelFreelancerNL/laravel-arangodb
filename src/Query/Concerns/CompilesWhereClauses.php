<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use Illuminate\Database\Query\Builder as IluminateBuilder;

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
     * @param  array<mixed>  $where
     */
    protected function whereBasic(IluminateBuilder $query, $where): string
    {
        $value = $this->parameter($where['value']);

        $operator = str_replace('?', '??', $where['operator']);

        return $this->wrap($where['column']).' '.$operator.' '.$value;
    }

    /**
     * Compile a "between" where clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereBetween(IluminateBuilder $query, $where): string
    {
        $predicate = [];

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
     * @param  array<mixed>  $where
     */
    protected function whereBetweenColumns(IluminateBuilder $query, $where): string
    {
        $predicate = [];

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
     * Compile a where clause comparing two columns.
     *
     * @param  array<mixed>  $where
     */
    protected function whereColumn(IluminateBuilder $query, $where): string
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
     * @param  array<mixed>  $where
     */
    protected function whereNull(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = '==';
        $predicate[2] = null;
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereNotNull(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = '!=';
        $predicate[2] = null;
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where in" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereIn(IluminateBuilder $query, $where): string
    {
        $predicate = [];

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
     * @param  array<mixed>  $where
     */
    protected function whereInRaw(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'IN';
        $predicate[2] = '[' . implode(', ', $where['values']) . ']';
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereNotIn(IluminateBuilder $query, $where): string
    {
        $predicate = [];

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
     * @param  array<mixed>  $where
     */
    protected function whereNotInRaw(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $predicate[0] = $this->normalizeColumn($query, $where['column']);
        $predicate[1] = 'NOT IN';
        $predicate[2] = '[' . implode(', ', $where['values']) . ']';
        $predicate[3] = $where['boolean'];
        return $predicate;
    }

    /**
     * Compile a "where JSON contains" clause.
     *
     * @param array<mixed> $where
     */
    protected function whereJsonContains(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $operator = $where['not'] ? 'NOT IN' : 'IN';

        $predicate[0] = $query->aqb->bind($where['value']);
        $predicate[1] = $operator;
        $predicate[2] = $this->normalizeColumn($query, $where['column']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where JSON length" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereJsonLength(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $column = $this->normalizeColumn($query, $where['column']);

        $predicate[0] = $query->aqb->length($column);
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($query, $where['value']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }


    /**
     * Compile a "where date" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereDate(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $query->aqb->dateFormat($this->normalizeColumn($query, $where['column']), "%yyyy-%mm-%dd");
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($query, $where['value']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where year" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereYear(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $query->aqb->dateYear($this->normalizeColumn($query, $where['column']));
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($query, $where['value']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where month" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereMonth(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $query->aqb->dateMonth($this->normalizeColumn($query, $where['column']));
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($query, $where['value']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }


    /**
     * Compile a "where day" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereDay(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $query->aqb->dateDay($this->normalizeColumn($query, $where['column']));
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($query, $where['value']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a "where time" clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereTime(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $where = $this->normalizeOperator($where);

        $predicate[0] = $query->aqb->dateFormat($this->normalizeColumn($query, $where['column']), "%hh:%ii:%ss");
        $predicate[1] = $where['operator'];
        $predicate[2] = $this->parameter($query, $where['value']);
        $predicate[3] = $where['boolean'];

        return $predicate;
    }

    /**
     * Compile a nested where clause.
     *
     * @param  array<mixed>  $where
     */
    protected function whereNested(IluminateBuilder $query, $where): string
    {
        $predicates = [];
        $predicates = $this->compileWheresToArray($where['query']);

        $query->aqb->binds = array_merge($query->aqb->binds, $where['query']->aqb->binds);
        $query->aqb->collections = array_merge_recursive($query->aqb->collections, $where['query']->aqb->collections);

        return $predicates;
    }

    /**
     * Compile a where condition with a sub-select.
     *
     * @param  array<mixed>  $where
     */
    protected function whereSub(IluminateBuilder $query, $where): string
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
    protected function whereExists(IluminateBuilder $query, $where): string
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
    protected function whereNotExists(IluminateBuilder $query, $where): string
    {
        $predicate = [];

        $predicate[0] = $where['query']->aqb;
        $predicate[1] = $where['operator'];
        $predicate[2] = $where['value'];
        $predicate[3] = $where['boolean'];

        return $predicate;
    }
}
