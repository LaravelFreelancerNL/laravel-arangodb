<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Closure;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

trait BuildsWhereClauses
{
    /**
     * Add a basic where clause to the query.
     *
     * @param  Closure|string|array|QueryBuilder  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return Builder
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        if ($column instanceof Closure && is_null($operator)) {
            return $this->whereNested($column, $boolean);
        }

        // If the column is a Closure instance and there is an operator value, we will
        // assume the developer wants to run a subquery and then compare the result
        // of that subquery with the given value that was provided to the method.
        if ($this->isQueryable($column) && ! is_null($operator)) {
            $sub = $this->createSub($column);

            return $this->where($sub, $operator, $value, $boolean);
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '==' and set the values appropriately.
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '=='];
        }

        // If the value is a Closure, it means the developer is performing an entire
        // sub-select within the query and we will need to compile the sub-select
        // within the where clause to get the appropriate query record results.
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }

        // If the value is "null", we will just assume the developer wants to add a
        // where null clause to the query. So, we will allow a short-cut here to
        // that method for convenience so the developer doesn't have to check.
        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator !== '=');
        }

        $type = 'Basic';

        // Now that we are working with just a simple query we can put the elements
        // in our array and add the query binding to our array of bindings that
        // will be bound to each SQL statements when it is finally executed.
        $this->wheres[] = compact(
            'type',
            'column',
            'operator',
            'value',
            'boolean'
        );

        return $this;
    }

    /**
     * Add an exists clause to the query.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param  Builder  $query
     * @param  string  $boolean
     * @param  bool  $not
     * @return Builder
     */
    public function addWhereExistsQuery(IlluminateQueryBuilder $query, $boolean = 'and', $not = false)
    {
        if ($not) {
            return $this->addWhereNotExistsQuery($query, $boolean = 'and');
        }

        $type = 'Exists';
        $operator = '>';
        $value = 0;

        $query->grammar->compileSelect($query);

        if ($query->limit != 1) {
            $query->aqb = $query->aqb->length($query->aqb);
        }

        if (isset($query->limit) && $query->limit == 1) {
            $query->aqb = $query->aqb->first($query->aqb);
            $operator = '!=';
            $value = null;
        }

        $this->wheres[] = compact('type', 'query', 'operator', 'value', 'boolean');

        return $this;
    }

    /**
     * Add an not exists clause to the query.
     *
     * @param  Builder  $query
     * @param  string  $boolean
     * @return Builder
     */
    public function addWhereNotExistsQuery(IlluminateQueryBuilder $query, $boolean = 'and')
    {
        $type = 'Exists';
        $operator = '==';
        $value = 0;

        $query->grammar->compileSelect($query);

        if ($query->limit != 1) {
            $query->aqb = $query->aqb->length($query->aqb);
        }

        if (isset($query->limit) && $query->limit == 1) {
            $query->aqb = $query->aqb->first($query->aqb);
            $value = null;
        }

        $this->wheres[] = compact('type', 'query', 'operator', 'value', 'boolean');

        return $this;
    }

    /**
     * Add a "where JSON contains" clause to the query.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param  string  $column
     * @param  mixed  $value
     * @param  string  $boolean
     * @param  bool  $not
     * @return IlluminateQueryBuilder
     */
    public function whereJsonContains($column, $value, $boolean = 'and', $not = false)
    {
        $type = 'JsonContains';

        $this->wheres[] = compact('type', 'column', 'value', 'boolean', 'not');

        return $this;
    }

    /**
     * Add a full sub-select to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  \Closure  $callback
     * @param  string  $boolean
     * @return Builder
     */
    protected function whereSub($column, $operator, Closure $callback, $boolean)
    {
        $type = 'Sub';

        // Once we have the query instance we can simply execute it so it can add all
        // of the sub-select's conditions to itself, and then we can cache it off
        // in the array of where clauses for the "main" parent query instance.
        call_user_func($callback, $query = $this->forSubQuery());

        $query->grammar->compileSelect($query);

        if (isset($query->limit) && $query->limit == 1) {
            //Return the value, not an array of values
            $query->aqb = $query->aqb->first($query->aqb);
        }

        $this->wheres[] = compact(
            'type',
            'column',
            'operator',
            'query',
            'boolean'
        );

        return $this;
    }
}
