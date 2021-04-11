<?php

namespace LaravelFreelancerNL\Aranguent;

use ArangoClient\ArangoClient;
use Illuminate\Database\Connection as IlluminateConnection;
use Iterator;
use LaravelFreelancerNL\Aranguent\Concerns\DetectsDeadlocks;
use LaravelFreelancerNL\Aranguent\Concerns\DetectsLostConnections;
use LaravelFreelancerNL\Aranguent\Concerns\HandlesArangoDb;
use LaravelFreelancerNL\Aranguent\Concerns\ManagesTransactions;
use LaravelFreelancerNL\Aranguent\Query\Builder as QueryBuilder;
use LaravelFreelancerNL\Aranguent\Query\Grammar as QueryGrammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use LaravelFreelancerNL\Aranguent\Schema\Builder as SchemaBuilder;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as FluentAQL;

class Connection extends IlluminateConnection
{
    use HandlesArangoDb;
    use DetectsDeadlocks;
    use DetectsLostConnections;
    use ManagesTransactions;

    protected ArangoClient $arangoClient;

    protected $database;

    /**
     * The ArangoDB driver name.
     *
     * @var string
     */
    protected string $driverName = 'arangodb';

    /**
     * Connection constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;

        $this->database = (isset($this->config['database'])) ? $this->config['database'] : null;
        $this->tablePrefix = isset($this->config['tablePrefix']) ? $this->config['tablePrefix'] : null;

        // activate and set the database client connection
        $this->arangoClient = new ArangoClient($this->config);

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so we initialize these to their default values while starting.
        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return SchemaBuilder
     */
    public function getSchemaBuilder(): SchemaBuilder
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return QueryGrammar
     */
    protected function getDefaultQueryGrammar(): QueryGrammar
    {
        return new QueryGrammar();
    }

    /**
     * Get the default post processor instance.
     *
     * @return Processor
     */
    protected function getDefaultPostProcessor(): Processor
    {
        return new Processor();
    }

    /**
     * Run a select statement against the database and returns a generator.
     * ($useReadPdo is a dummy to adhere to the interface).
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool|null  $useReadPdo
     * @return Iterator|null
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
     * @param string|FluentAQL $query
     * @param array            $bindings
     *
     * @return bool
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
     * @param string|FluentAQL $query
     * @param array            $bindings
     *
     * @return int
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
     *
     * @return bool
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
     * @param  array  $bindings
     *
     * @return array
     */
    public function explain(string $query, $bindings = []): array
    {
        $statement = $this->arangoClient->prepare($query, $bindings);

        return $statement->explain();
    }

    /**
     * Run a select statement against the database.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param string|FluentAQL $query
     * @param array            $bindings
     * @param bool             $useReadPdo
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
     * @param string|FluentAQL $query
     * @param array<mixed>|null     $bindings
     * @param bool             $useReadPdo
     *
     * @return mixed
     */
    public function execute($query, ?array $bindings = [], $useReadPdo = true)
    {
        // Usage of a separate DB to read date isn't supported at this time
        $useReadPdo = null;

        if ($query instanceof FluentAQL) {
            $bindings = $query->binds;
            $query = $query->query;
        }

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
     *
     * @return QueryBuilder
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
     * Get the collection prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Disconnect from the underlying ArangoDB connection.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->transactions = 0;

        $this->arangoClient = null;
    }

    /**
     * Reconnect to the database if a Arango connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->arangoClient)) {
            $this->reconnect();
        }
    }

    public function getArangoClient(): ArangoClient
    {
        return $this->arangoClient;
    }

    /**
     * @param  string  $database
     */
    public function setDatabaseName($database): void
    {
        $this->database = $database;
        $this->arangoClient->setDatabase($database);
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    /**
     * @param  FluentAQL|string  $query
     * @param  array  $bindings
     * @return array
     */
    private function handleQueryBuilder($query, array $bindings): array
    {
        if ($query instanceof FluentAQL) {
            $bindings = $query->binds;
            $query = $query->query;
        }
        return [$query, $bindings];
    }
}
