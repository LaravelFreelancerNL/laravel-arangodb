<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Concerns;

use Closure;
use Exception;
use Iterator;
use LaravelFreelancerNL\Aranguent\Query\Builder as ArangoBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder as QueryBuilder;
use LaravelFreelancerNL\Aranguent\QueryException;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as ArangoQueryBuilder;
use stdClass;

trait RunsQueries
{
    /**
     * Run a select statement against the database and returns a generator.
     * ($useReadPdo is a dummy to adhere to the interface).
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool|null  $useReadPdo
     */
    public function cursor($query, $bindings = [], $useReadPdo = null): ?Iterator
    {
        // Usage of a separate DB to read date isn't supported at this time
        $useReadPdo = null;

        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $statement = $this->arangoClient->prepare($query, $bindings);

            return $statement->execute();
        });
    }

    /**
     * Execute an AQL statement and return the boolean result.
     *
     * @param  string|ArangoQueryBuilder  $query
     * @param  array  $bindings
     */
    public function statement($query, $bindings = []): bool
    {
        [$query, $bindings] = $this->handleQueryBuilder(
            $query,
            $bindings
        );

        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $statement = $this->arangoClient->prepare($query, $bindings);

            $statement->execute();

            $affectedDocumentCount = $statement->getWritesExecuted();
            $this->recordsHaveBeenModified($changed = $affectedDocumentCount > 0);

            return $changed;
        });
    }

    /**
     * Run an AQL statement and get the number of rows affected.
     *
     * @param  string|ArangoQueryBuilder  $query
     * @param  array  $bindings
     */
    public function affectingStatement($query, $bindings = []): int
    {
        [$query, $bindings] = $this->handleQueryBuilder(
            $query,
            $bindings
        );

        return $this->run($query, $bindings, function () use ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and get the executed writes from the extra.
            $statement = $this->arangoClient->prepare($query, $bindings);

            $statement->execute();

            $affectedDocumentCount = $statement->getWritesExecuted();

            $this->recordsHaveBeenModified($affectedDocumentCount > 0);

            return $affectedDocumentCount;
        });
    }

    /**
     * Run a raw, unprepared query against the connection.
     *
     * @param  string  $query
     */
    public function unprepared($query): bool
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {
                return true;
            }

            $statement = $$this->arangoClient->prepare($query);
            $statement->execute();
            $affectedDocumentCount = $statement->getWritesExecuted();
            $change = $affectedDocumentCount > 0;

            $this->recordsHaveBeenModified($change);

            return $change;
        });
    }

    /**
     * Returns the query execution plan. The query will not be executed.
     *
     * @param  string  $query
     * @param  array<mixed>  $bindings
     */
    public function explain(string|ArangoQueryBuilder $query, $bindings = []): stdClass
    {
        [$query, $bindings] = $this->handleQueryBuilder(
            $query,
            $bindings
        );

        $statement = $this->arangoClient->prepare($query, $bindings);

        return $statement->explain();
    }

    /**
     * @param  ArangoQueryBuilder|string  $query
     */
    protected function handleQueryBuilder($query, array $bindings): array
    {

        if ($query instanceof ArangoQueryBuilder) {
            $bindings = $query->binds;
            $query = $query->query;
        }

        if ($query instanceof ArangoBuilder) {
            $bindings = $query->getBindings();
            $query = $query->toSql();
            //            dd("handleQueryBuilder query", $query->toSql(), "bindings", $query->getBindings());
        }

        return [$query, $bindings];
    }

    /**
     * Run a select statement against the database.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param  string|ArangoQueryBuilder  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->execute($query, $bindings, $useReadPdo);
    }

    /**
     * Run an AQL query against the database and return the results.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param  string|ArangoQueryBuilder  $query
     * @param  array<mixed>|null  $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     */
    public function execute($query, ?array $bindings = [], $useReadPdo = true)
    {
        // Usage of a separate DB to read date isn't supported at this time
        $useReadPdo = null;

        [$query, $bindings] = $this->handleQueryBuilder(
            $query,
            $bindings
        );

        return $this->run($query, $bindings, function () use ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $statement = $this->arangoClient->prepare($query, $bindings);

            $statement->execute();

            return $statement->fetchAll();
        });
    }

    /**
     * Get a new query builder instance.
     */
    public function query(): QueryBuilder
    {
        return new QueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return mixed
     *
     * @throws QueryException
     */
    protected function run($query, $bindings, Closure $callback)
    {
        foreach ($this->beforeExecutingCallbacks as $beforeExecutingCallback) {
            $beforeExecutingCallback($query, $bindings, $this);
        }

        $this->reconnectIfMissingConnection();

        $start = microtime(true);

        // Here we will run this query. If an exception occurs we'll determine if it was
        // caused by a connection that has been lost. If that is the cause, we'll try
        // to re-establish connection and re-run the query with a fresh connection.
        try {
            $result = $this->runQueryCallback($query, $bindings, $callback);
        } catch (QueryException $e) {
            $result = $this->handleQueryException(
                $e,
                $query,
                $bindings,
                $callback
            );
        }

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $this->logQuery(
            $query,
            $bindings,
            $this->getElapsedTime($start)
        );

        return $result;
    }

    /**
     * Run a SQL statement.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return mixed
     *
     * @throws QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            return $callback($query, $bindings);
        } catch (Exception $e) {
            // If an exception occurs when attempting to run a query, we'll format the error
            // message to include the bindings with SQL, which will make this exception a
            // lot more helpful to the developer instead of just the database's errors.

            throw new QueryException(
                $this->getName(),
                $query,
                $this->prepareBindings($bindings),
                $e
            );
        }
    }
}
