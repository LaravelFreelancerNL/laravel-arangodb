<?php

namespace LaravelFreelancerNL\Aranguent\Query;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException;
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

        return end($response);
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
        if (! $results->isEmpty()) {
            return $results[0];
        }

        return false;
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
