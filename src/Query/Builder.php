<?php

namespace LaravelFreelancerNL\Aranguent\Query;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Query\Grammars\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processors\Processor;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

class Builder extends IlluminateQueryBuilder
{
    /**
     * @var QueryBuilder
     */
    public $aqb;

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
        if (!$aqb instanceof QueryBuilder) {
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
        return $this->connection->select($this->grammar->compileSelect($this));
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toSql()
    {
        return $this->grammar->compileSelect($this)->query;
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $qb = $this->grammar->compileInsertGetId($this, $values, $sequence);

        $result = $this->getConnection()->execute($qb);

        return $result;
    }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        $aqb = $this->grammar->compileUpdate($this, $values);

        return $this->connection->update($aqb);
    }

    /**
     * Delete a record from the database.
     *
     * @param  mixed  $_key
     * @return int
     */
    public function delete($_key = null)
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
        if (! is_null($_key)) {
            $this->where($this->from.'._key', '=', $_key);
        }
        $aqb = $this->grammar->compileDelete($this, $_key);
        return $this->connection->delete($aqb);
    }


}
