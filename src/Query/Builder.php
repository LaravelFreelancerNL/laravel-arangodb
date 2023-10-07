<?php

namespace LaravelFreelancerNL\Aranguent\Query;

use Closure;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsJoinClauses;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsSubqueries;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsWhereClauses;
use LaravelFreelancerNL\Aranguent\Query\Concerns\ConvertsIdToKey;
use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException;
use LaravelFreelancerNL\FluentAQL\Expressions\FunctionExpression;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

class Builder extends IlluminateQueryBuilder
{
    use BuildsJoinClauses;
    use BuildsSubqueries;
    use BuildsWhereClauses;
    use ConvertsIdToKey;

    public QueryBuilder|FunctionExpression $aqb;

    /**
     * The current query value bindings.
     *
     * @var array
     */
    public $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'groupBy' => [],
        'having' => [],
        'order' => [],
        'union' => [],
        'unionOrder' => [],
        'insert' => [],
    ];


    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var Grammar
     */
    public $grammar;

    /**
     * The current query value bindings.
     *
     * @var null|array{predicates: array<mixed>, options: array<string, string>}
     */
    public ?array $search = null;

    /**
     * The query variables that should be set.
     *
     * @var array<mixed>
     */
    public $variables = [];

    /**
     * ID of the query
     * Used as prefix for automatically generated bindings.
     *
     * @var int
     */
    protected $queryId = 1;

    /**
     * @override
     * Create a new query builder instance.
     */
    public function __construct(
        Connection $connection,
        Grammar $grammar = null,
        Processor $processor = null,
        QueryBuilder $aqb = null
    ) {
        $this->connection = $connection;
        $this->grammar = $grammar ?: $connection->getQueryGrammar();
        $this->processor = $processor ?: $connection->getPostProcessor();
        if (! $aqb instanceof QueryBuilder) {
            $aqb = new QueryBuilder();
        }
        $this->aqb = $aqb;

        $this->queryId = spl_object_id($this);
    }

    protected function bindValue($value, string $type = 'where')
    {
        if (! $value instanceof Expression) {
            $this->addBinding($value, $type);
            $value = $this->replaceValueWithBindVariable($type);
        }

        return $value;
    }


    protected function generateBindVariable(string $type = 'where'): string
    {
        return $this->queryId.'_'.$type.'_'.(count($this->bindings[$type]) + 1);
    }

    /**
     * Add a binding to the query.
     *
     * @param  mixed  $value
     * @param  string  $type
     * @return IlluminateQueryBuilder
     *
     * @throws \InvalidArgumentException
     */
    public function addBinding($value, $type = 'where')
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        $bindVariable = $this->generateBindVariable($type);

        $this->bindings[$type][$bindVariable] = $this->castBinding($value);

        return $this;
    }

    protected function getLastBindVariable(string $type = 'where')
    {
        return array_key_last($this->bindings[$type]);
    }

    protected function replaceValueWithBindVariable(string $type = 'where')
    {
        return '@'.$this->getLastBindVariable($type);
    }

    /**
     * Remove all of the expressions from a list of bindings.
     *
     * @param  array  $bindings
     * @return array
     */
    public function cleanBindings(array $bindings)
    {
        return collect($bindings)
            ->reject(function ($binding) {
                return $binding instanceof ExpressionContract;
            })
            ->map([$this, 'castBinding'])
            ->all();
    }

    public function getQueryId()
    {
        return $this->queryId;
    }

    /**
     * Get the current query value bindings in a flattened array.
     *
     * @return array
     */
    public function getBindings()
    {
        $extractedBindings = [];
        foreach ($this->bindings as $typeBinds) {
            foreach ($typeBinds as $key => $value) {
                $extractedBindings[$key] = $value;
            }
        }

        return $extractedBindings;
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param  \Closure|IlluminateQueryBuilder|string  $table
     * @param  string|null  $as
     * @return IlluminateQueryBuilder
     */
    public function from($table, $as = null)
    {
        if ($this->isQueryable($table)) {
            return $this->fromSub($table, $as);
        }
        $this->grammar->registerTableAlias($table, $as);

        $this->from = $table;

        return $this;
    }

    /**
     * Run a pagination count query.
     *
     * @param  array<mixed>  $columns
     * @return array<mixed>
     */
    protected function runPaginationCountQuery($columns = ['*'])
    {
        $without = $this->unions ? ['orders', 'limit', 'offset'] : ['columns', 'orders', 'limit', 'offset'];

        $closeResults = $this->cloneWithout($without)
            ->cloneWithoutBindings($this->unions ? ['order'] : ['select', 'order'])
            ->setAggregate('count', $this->withoutSelectAliases($columns))
            ->get()->all();

        return $closeResults;
    }

    /**
     * Set the columns to be selected.
     *
     * @param  array<mixed>|mixed  $columns
     */
    public function select($columns = ['*']): IlluminateQueryBuilder
    {
        $this->columns = [];
        $this->bindings['select'] = [];

        $columns = is_array($columns) ? $columns : func_get_args();

        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {

                $this->selectSub($column, $as);
            } else {
                $this->addColumns([$as => $column]);
            }
        }

        return $this;
    }

    /**
     * Add a new select column to the query.
     *
     * @param  array|mixed  $column
     * @return $this
     */
    public function addSelect($column)
    {
        $columns = is_array($column) ? $column : func_get_args();

        $this->addColumns($columns);

        return $this;
    }

    /**
     * @param  array<mixed>  $columns
     */
    protected function addColumns(array $columns): void
    {
        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {
                if (is_null($this->columns)) {
                    $this->select($this->from.'.*');
                }

                $this->selectSub($column, $as);

                continue;
            }

            if (is_string($as)) {
                $this->columns[$as] = $column;

                continue;
            }

            $this->columns[] = $column;
        }
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array<mixed>  $values
     *
     * @throws BindException
     */
    public function insert(array $values): bool
    {
        if (! array_is_list($values)) {
            $values = [$values];
        }

        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        $bindVar = $this->bindValue($values, 'insert');

        $aql = $this->grammar->compileInsert($this, $values, $bindVar);
        $results = $this->getConnection()->insert($aql, $this->getBindings());

        return $results;
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array<mixed>  $values
     */
    public function insertGetId(array $values, $sequence = null)
    {
        if (! array_is_list($values)) {
            $values = [$values];
        }

        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        $bindVar = $this->bindValue($values, 'insert');

        $aql =  $this->grammar->compileInsertGetId($this, $values, $sequence, $bindVar);
        $response = $this->getConnection()->execute($aql, $this->getBindings());
        $this->aqb = new QueryBuilder();

        return (is_array($response)) ? end($response) : $response;
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array<mixed>  $values
     *
     * @throws BindException
     */
    public function insertOrIgnore(array $values): bool
    {
        if (! array_is_list($values)) {
            $values = [$values];
        }

        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        $bindVar = $this->bindValue($values, 'insert');


        $aql = $this->grammar->compileInsertOrIgnore($this, $values, $bindVar);
        $results = $this->getConnection()->insert($aql, $this->getBindings());

        return $results;
    }

    /**
     * Delete records from the database.
     *
     * @param  mixed  $id
     * @return int
     */
    public function delete($id = null)
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
        if (! is_null($id)) {
            $this->where($this->from.'._key', '=', $id);
        }

        $this->applyBeforeQueryCallbacks();

        return $this->connection->delete(
            $this->grammar->compileDelete($this),
            $this->cleanBindings(
                $this->grammar->prepareBindingsForDelete($this->bindings)
            )
        );
    }

    protected function setAql()
    {
        $this->aqb = $this->aqb->get();

        return $this;
    }

    /**
     * Update a record in the database.
     *
     * @param  array<mixed>  $values
     * @return int
     */
    public function update(array $values)
    {
        $this->aqb = new QueryBuilder();

        $this->grammar->compileUpdate($this, $values)->setAql();
        $results = $this->connection->update($this->aqb);
        $this->aqb = new QueryBuilder();

        return $results;
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param  string  $function
     * @param  array<mixed>  $columns
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
     * Determine if the given operator is supported.
     *
     * @param  string  $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return ! in_array(strtolower($operator), $this->operators, true) &&
            ! isset($this->grammar->getOperators()[strtoupper($operator)]);
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|\Illuminate\Contracts\Database\Query\Expression|string  $column
     * @param  string  $direction
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function orderBy($column, $direction = 'asc')
    {
        if ($this->isQueryable($column)) {
            [$query, $bindings] = $this->createSub($column);

            $column = new Expression('('.$query.')');

            $this->addBinding($bindings, $this->unions ? 'unionOrder' : 'order');
        }

        $direction = strtoupper($direction);

        if (! in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Order direction must be "asc" or "desc".');
        }

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function orderByRaw($sql, $bindings = [])
    {
        $type = 'Raw';

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = compact('type', 'sql');

        if (!isset($this->bindings[$this->unions ? 'unionOrders' : 'orders'])) {
            $this->bindings[$this->unions ? 'unionOrders' : 'orders'] = $bindings;

            return $this;
        }

        $this->bindings[$this->unions ? 'unionOrders' : 'orders'] = array_merge(
            $this->bindings[$this->unions ? 'unionOrders' : 'orders'],
            $bindings
        );

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
        // ArangoDB's random function doesn't accept a seed.
        $seed = null;

        return $this->orderByRaw($this->grammar->compileRandom($this));
    }

    /**
     * Search an ArangoSearch view.
     */
    public function search(mixed $predicates, array $options = null): Builder
    {
        if ($predicates instanceof Closure) {
            $predicates = $predicates($this->aqb);
        }

        if (! is_array($predicates)) {
            $predicates = [$predicates];
        }

        $this->search = [
            'predicates' => $predicates,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Create a new query instance for a sub-query.
     *
     * @return $this
     */
    protected function forSubQuery()
    {
        return $this->newQuery();
    }

    /**
     * Get the database connection instance.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
