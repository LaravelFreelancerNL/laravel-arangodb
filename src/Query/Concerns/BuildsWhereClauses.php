<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Carbon\CarbonPeriod;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait BuildsWhereClauses
{
    /**
     * Add a date based (year, month, day, time) statement to the query.
     *
     * @param  string  $type
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return IlluminateQueryBuilder
     */
    protected function addDateBasedWhere($type, $column, $operator, $value, $boolean = 'and')
    {
        $value = $this->bindValue($value);

        $this->wheres[] = compact('column', 'type', 'boolean', 'operator', 'value');

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
     * Add a basic where clause to the query.
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return IlluminateQueryBuilder
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
            $value, $operator, func_num_args() === 2
        );

        // If the column is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parentheses.
        // We will add that Closure to the query and return back out immediately.
        if ($column instanceof Closure && is_null($operator)) {
            return $this->whereNested($column, $boolean);
        }

        // If the column is a Closure instance and there is an operator value, we will
        // assume the developer wants to run a subquery and then compare the result
        // of that subquery with the given value that was provided to the method.
        if ($this->isQueryable($column) && ! is_null($operator)) {
            [$sub, $bindings] = $this->createSub($column);

            return $this->addBinding($bindings, 'where')
                ->where(new Expression('('.$sub.')'), $operator, $value, $boolean);
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
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
            return $this->whereNull($column, $boolean, $operator !== '==');
        }

        $type = 'Basic';

        // If the column is making a JSON reference we'll check to see if the value
        // is a boolean. If it is, we'll add the raw boolean string as an actual
        // value to the query to ensure this is properly handled by the query.
        if (str_contains($column, '->') && is_bool($value)) {
            $value = new Expression($value ? 'true' : 'false');

            if (is_string($column)) {
                $type = 'JsonBoolean';
            }
        }

        if ($this->isBitwiseOperator($operator)) {
            $type = 'Bitwise';
        }

        $value = $this->bindValue($value);

        // Now that we are working with just a simple query we can put the elements
        // in our array and add the query binding to our array of bindings that
        // will be bound to each SQL statements when it is finally executed.
        $this->wheres[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );

        return $this;
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  string|Expression $column
     * @param  iterable  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return IlluminateQueryBuilder
     */
    public function whereBetween($column, iterable $values, $boolean = 'and', $not = false)
    {
        $type = 'between';

        if ($values instanceof CarbonPeriod) {
            $values = $values->toArray();
        }

        $values = array_slice($this->cleanBindings(Arr::flatten($values)), 0, 2);
        $values[0] = $this->bindValue($values[0]);
        $values[1] = $this->bindValue($values[1]);

        $this->wheres[] = compact('type', 'column', 'values', 'boolean', 'not');

        return $this;
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return IlluminateQueryBuilder
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';

        // If the value is a query builder instance we will assume the developer wants to
        // look for any values that exist within this given query. So, we will add the
        // query accordingly so that this query is properly executed when it is run.
        if ($this->isQueryable($values)) {
            [$query, $bindings] = $this->createSub($values);

            $values = [new Expression($query)];

            $this->addBinding($bindings, 'where');
        }

        // Next, if the value is Arrayable we need to cast it to its raw array form so we
        // have the underlying array value instead of an Arrayable object which is not
        // able to be added as a binding, etc. We will then add to the wheres array.
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $values = $this->bindValue($this->cleanBindings($values));

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

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

        $value = $this->bindValue($value);

        $this->wheres[] = compact('type', 'column', 'value', 'boolean', 'not');

        return $this;
    }

    /**
     * Add a "where JSON length" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return IlluminateQueryBuilder
     */
    public function whereJsonLength($column, $operator, $value = null, $boolean = 'and')
    {
        $type = 'JsonLength';

        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $value = $this->bindValue((int) $this->flattenValue($value));

        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');

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
