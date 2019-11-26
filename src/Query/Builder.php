<?php

namespace LaravelFreelancerNL\Aranguent\Query;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException;
use LaravelFreelancerNL\FluentAQL\Expressions\ExpressionInterface as ExpressionInterface;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

class Builder extends IlluminateQueryBuilder
{
    /**
     * @var Grammar
     */
    public $grammar;

    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var QueryBuilder
     */
    public $aqb;

    /**
     * Alias' are AQL variables
     * Sticking with the SQL based naming as this is the Laravel driver.
     * @var QueryBuilder
     */
    protected $aliasRegistry = [];

    /**
     * @override
     * Create a new query builder instance.
     *
     * @param ConnectionInterface $connection
     * @param Grammar $grammar
     * @param Processor $processor
     * @param QueryBuilder|null $aqb
     */
    public function __construct(ConnectionInterface $connection,
                                Grammar $grammar = null,
                                Processor $processor = null,
                                QueryBuilder $aqb = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?: $connection->getQueryGrammar();
        $this->processor = $processor ?: $connection->getPostProcessor();
        if (! $aqb instanceof QueryBuilder) {
            $aqb = new QueryBuilder();
        }
        $this->aqb = $aqb;
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
    protected function runSelect()
    {
        $response = $this->connection->select($this->grammar->compileSelect($this)->aqb);
        $this->aqb = new QueryBuilder();

        return $response;
    }

    /**
     * Run a pagination count query.
     *
     * @param  array  $columns
     * @return array
     */
    protected function runPaginationCountQuery($columns = ['*'])
    {
        $without = $this->unions ? ['orders', 'limit', 'offset'] : ['columns', 'orders', 'limit', 'offset'];

        $closeResults = $this->cloneWithout($without)
            ->cloneWithoutBindings($this->unions ? ['order'] : ['select', 'order'])
            ->setAggregate('count', $this->withoutSelectAliases($columns))
            ->get()->all();

        $this->aqb = new QueryBuilder();

        return $closeResults;
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toSql()
    {
        return $this->grammar->compileSelect($this)->aqb->query;
    }

    /**
     * Insert a new record into the database.
     * @param array $values
     * @return bool
     * @throws BindException
     */
    public function insert(array $values) : bool
    {
        $response = $this->getConnection()->insert($this->grammar->compileInsert($this, $values)->aqb);
        $this->aqb = new QueryBuilder();

        return $response;
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param array $values
     * @param string|null $sequence
     * @return int
     * @throws BindException
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $response = $this->getConnection()->execute($this->grammar->compileInsertGetId($this, $values, $sequence)->aqb);
        $this->aqb = new QueryBuilder();

        return (is_array($response)) ? end($response) : $response;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array|string  $columns
     * @return Collection
     */
    public function get($columns = ['*'])
    {
        $results = collect($this->onceWithColumns(Arr::wrap($columns), function () {
            return $this->runSelect();
        }));

        return $results;
    }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        $response = $this->connection->update($this->grammar->compileUpdate($this, $values)->aqb);
        $this->aqb = new QueryBuilder();

        return $response;
    }

    /**
     * Delete a record from the database.
     *
     * @param  mixed  $_key
     * @return int
     */
    public function delete($_key = null)
    {
        $response = $this->connection->delete($this->grammar->compileDelete($this, $_key)->aqb);
        $this->aqb = new QueryBuilder();

        return $response;
    }

    public function registerAlias(string $table, string $alias) : void
    {
        $this->aliasRegistry[$table] = $alias;
    }

    public function getAlias(string $table) : string
    {
        return $this->aliasRegistry[$table];
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param  string  $function
     * @param  array   $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        $results = $this->cloneWithout($this->unions ? [] : ['columns'])
            ->setAggregate($function, $columns)
            ->get($columns);

        $this->aqb = new QueryBuilder();

        if (! $results->isEmpty()) {
            return array_change_key_case((array) $results[0])['aggregate'];
        }

        return false;
    }



    /**
     * Add a basic where clause to the query.
     *
     * @param Closure|string|array $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
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
            $value, $operator, func_num_args() === 2
        );

        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        // If the value is a Closure, it means the developer is performing an entire
        // sub-select within the query and we will need to compile the sub-select
        // within the where clause to get the appropriate query record results.
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }

        $type = 'Basic';

        // If the column is making a JSON reference we'll check to see if the value
        // is a boolean. If it is, we'll add the raw boolean string as an actual
        // value to the query to ensure this is properly handled by the query.
        if (Str::contains($column, '->') && is_bool($value)) {
            $value = new Expression($value ? 'true' : 'false');

            if (is_string($column)) {
                $type = 'JsonBoolean';
            }
        }

        // Now that we are working with just a simple query we can put the elements
        // in our array and add the query binding to our array of bindings that
        // will be bound to each SQL statements when it is finally executed.
        $this->wheres[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );

        if (! $value instanceof Expression) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    /**
     * Add a "where null" clause to the query.
     *
     * @param  string|array  $columns
     * @param  string  $boolean
     * @param  bool    $not
     * @return $this
     */
    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $type = 'Basic';
        $operator = $not ? '!=' : '==';
        $value = null;

        foreach (Arr::wrap($columns) as $column) {
            $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');
        }

        return $this;
    }

    /**
     * Determine if the given operator is supported.
     *
     * @param  string  $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return ! in_array(strtolower($operator), $this->operators, true) &&
            ! isset($this->grammar->getOperators()[strtolower($operator)]);
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return IlluminateQueryBuilder|static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  Closure|IlluminateQueryBuilder|string  $column
     * @param  string  $direction
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function orderBy($column, $direction = 'asc')
    {
        if ($this->isQueryable($column)) {
            [$query, $bindings] = $this->createSub($column);

            $column = new Expression('('.$query.')');
        }

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Add a raw "order by" clause to the query.
     *
     * @param string|ExpressionInterface $aql
     * @param array $bindings
     * @return $this
     */
    public function orderByRaw($aql, $bindings = [])
    {
        $type = 'Raw';
        $column = $aql;
        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = compact('type', 'column');

        return $this;
    }

    /**
     * Put the query's results in random order.
     *
     * @param  string  $seed
     * @return $this
     */
    public function inRandomOrder($seed = '')
    {
        return $this->orderByRaw($this->grammar->compileRandom($this));
    }
}
