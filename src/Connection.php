<?php

namespace LaravelFreelancerNL\Aranguent;

use ArangoDBClient\Connection as ArangoConnection;
use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;
use ArangoDBClient\Exception;
use ArangoDBClient\Statement;
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

    /**
     * {@inheritdoc}
     *
     * @var array
     */
    protected $defaultConfig = [
        ArangoConnectionOptions::OPTION_ENDPOINT    => 'tcp://localhost:8529',
        ArangoConnectionOptions::OPTION_CONNECTION  => 'Keep-Alive',
        ArangoConnectionOptions::OPTION_AUTH_USER   => null,
        ArangoConnectionOptions::OPTION_AUTH_PASSWD => null,
        'tablePrefix'                               => '',
    ];

    protected $config;

    protected $arangoConnection;

    protected $reconnector;

    protected $database;

    protected $schemaGrammar;

    protected $queryGrammar;

    protected $pretending;

    protected $recordsModified;

    protected $loggingQueries;

    protected $queryLog;

    /**
     * The ArangoDB driver name.
     *
     * @var string
     */
    protected $driverName = 'arangodb';

    /**
     * Connection constructor.
     *
     * @param array $config
     *
     * @throws Exception
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->defaultConfig, $config);

        if (isset($this->config['database'])) {
            $this->database = $this->config['database'];
        }

        $this->tablePrefix = $this->config['tablePrefix'];

        // activate and set the database client connection
        $this->arangoConnection = new ArangoConnection($this->config);

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
    public function getSchemaBuilder()
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
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar();
    }

    /**
     * Get the default post processor instance.
     *
     * @return Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor();
    }

    /**
     * Run a select statement against the database and returns a generator.
     * ($useReadPdo is a dummy to adhere to the interface).
     *
     * @param string     $query
     * @param array      $bindings
     * @param bool       $useReadPdo
     * @param array|null $transactionCollections
     *
     * @throws Exception
     *
     * @return Iterator|null
     */
    public function cursor($query, $bindings = [], $useReadPdo = null, $transactionCollections = [])
    {
        // Usage of a separate DB to read date isn't supported at this time
        $useReadPdo = null;

        return $this->run($query, $bindings, function ($query, $bindings) use ($transactionCollections) {
            if ($this->pretending()) {
                return [];
            }
            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query, $bindings, $transactionCollections);

                return [];
            }

            $statement = $this->newArangoStatement($query, $bindings);

            return $statement->execute();
        });
    }

    /**
     * Execute an AQL statement and return the boolean result.
     *
     * @param string|FluentAQL $query
     * @param array            $bindings
     * @param array|null       $transactionCollections
     *
     * @return bool
     */
    public function statement($query, $bindings = [], $transactionCollections = [])
    {
        if ($query instanceof FluentAQL) {
            $bindings = $query->binds;
            $transactionCollections = $query->collections;
            $query = $query->query;
        }

        return $this->run($query, $bindings, function ($query, $bindings) use ($transactionCollections) {
            if ($this->pretending()) {
                return true;
            }

            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query, $bindings, $transactionCollections);

                return true;
            }

            $statement = $this->newArangoStatement($query, $bindings);

            $cursor = $statement->execute();

            $affectedDocumentCount = $cursor->getWritesExecuted();
            $this->recordsHaveBeenModified($changed = $affectedDocumentCount > 0);

            return $changed;
        });
    }

    /**
     * Run an AQL statement and get the number of rows affected.
     *
     * @param string|FluentAQL $query
     * @param array            $bindings
     * @param array|null       $transactionCollections
     *
     * @return int
     */
    public function affectingStatement($query, $bindings = [], $transactionCollections = [])
    {
        [$query, $bindings, $transactionCollections] = $this->handleQueryBuilder(
            $query,
            $bindings,
            $transactionCollections
        );

        return $this->run($query, $bindings, function () use ($query, $bindings, $transactionCollections) {
            if ($this->pretending()) {
                return 0;
            }

            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query, $bindings, $transactionCollections);

                return 0;
            }

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and get the executed writes from the extra.
            $statement = $this->newArangoStatement($query, $bindings);

            $cursor = $statement->execute();

            $affectedDocumentCount = $cursor->getWritesExecuted();

            $this->recordsHaveBeenModified($affectedDocumentCount > 0);

            return $affectedDocumentCount;
        });
    }

    /**
     * Run a raw, unprepared query against the connection.
     *
     * @param string $query
     *
     * @return bool
     */
    public function unprepared($query)
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {
                return true;
            }
            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query);

                return [];
            }

            $statement = $this->newArangoStatement($query, []);

            $cursor = $statement->execute();

            $affectedDocumentCount = $cursor->getWritesExecuted();

            $change = $affectedDocumentCount > 0;

            $this->recordsHaveBeenModified($change);

            return $change;
        });
    }

    /**
     * Returns the query execution plan. The query will not be executed.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @throws Exception
     *
     * @return array
     */
    public function explain($query, $bindings = [])
    {
        $statement = $this->newArangoStatement($query, $bindings);

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
     * @param null|array       $transactionCollections
     *
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true, $transactionCollections = [])
    {
        return $this->execute($query, $bindings, $useReadPdo, $transactionCollections);
    }

    /**
     * Run an AQL query against the database and return the results.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param string|FluentAQL $query
     * @param array            $bindings
     * @param bool             $useReadPdo
     * @param null|array       $transactionCollections
     *
     * @return array
     */
    public function execute($query, $bindings = [], $useReadPdo = true, $transactionCollections = [])
    {
        // Usage of a separate DB to read date isn't supported at this time
        $useReadPdo = null;

        if ($query instanceof FluentAQL) {
            $bindings = $query->binds;
//            $transactionCollections = $query->collections;
            $query = $query->query;
        }

        return $this->run($query, $bindings, function () use ($query, $bindings, $transactionCollections) {
            if ($this->pretending()) {
                return [];
            }
            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query, $bindings, $transactionCollections);

                return [];
            }

            $statement = $this->newArangoStatement($query, $bindings);
            $cursor = $statement->execute();

            return $cursor->getAll();
        });
    }

    /**
     * Run an insert statement against the database.
     *
     * @param string|FluentAQL $query
     * @param array            $bindings
     * @param array|null       $transactionCollections
     *
     * @return bool
     */
    public function insert($query, $bindings = [], $transactionCollections = [])
    {
        return $this->statement($query, $bindings, $transactionCollections);
    }

    /**
     * Run an update statement against the database.
     *
     * @param string|FluentAQL $query
     * @param array            $bindings
     * @param array|null       $transactionCollections
     *
     * @return int
     */
    public function update($query, $bindings = [], $transactionCollections = [])
    {
        return $this->affectingStatement($query, $bindings, $transactionCollections);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string     $query
     * @param array      $bindings
     * @param array|null $transactionCollections
     *
     * @return int
     */
    public function delete($query, $bindings = [], $transactionCollections = [])
    {
        return $this->affectingStatement($query, $bindings, $transactionCollections);
    }

    /**
     * Get a new query builder instance.
     *
     * @return QueryBuilder
     */
    public function query()
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
    public function getTablePrefix()
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

        $this->arangoConnection = null;
    }

    /**
     * Reconnect to the database if a Arango connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->arangoConnection)) {
            $this->reconnect();
        }
    }

    public function getArangoConnection()
    {
        return $this->arangoConnection;
    }

    /**
     * @param $query
     * @param $bindings
     * @return Statement
     * @throws Exception
     */
    protected function newArangoStatement($query, $bindings): Statement
    {
        $statement = new Statement($this->arangoConnection, ['query' => $query, 'bindVars' => $bindings]);
        $statement->setDocumentClass(Document::class);

        return $statement;
    }


    public function setDatabaseName($database)
    {
        $this->database = $database;
        $this->arangoConnection->setDatabase($database);
    }

    public function getDatabaseName()
    {
        return $this->database;
    }

    /**
     * @param  FluentAQL|string  $query
     * @param  array  $bindings
     * @param  array|null  $transactionCollections
     * @return array
     */
    private function handleQueryBuilder($query, array $bindings, ?array $transactionCollections)
    {
        if ($query instanceof FluentAQL) {
            $bindings = $query->binds;
            $transactionCollections = $query->collections;
            $query = $query->query;
        }
        return [$query, $bindings, $transactionCollections];
    }
}
