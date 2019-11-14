<?php

namespace LaravelFreelancerNL\Aranguent;

use ArangoDBClient\Exception;
use ArangoDBClient\Statement;
use ArangoDBClient\Connection as ArangoConnection;
use ArangoDBClient\GraphHandler as ArangoGraphHandler;
use ArangoDBClient\UserHandler as ArangoUserHandler;
use Illuminate\Database\Connection as IlluminateConnection;
use ArangoDBClient\DocumentHandler as ArangoDocumentHandler;
use Iterator;
use LaravelFreelancerNL\Aranguent\Concerns\DetectsDeadlocks;
use LaravelFreelancerNL\Aranguent\Query\Processors\Processor;
use LaravelFreelancerNL\Aranguent\Concerns\ManagesTransactions;
use ArangoDBClient\CollectionHandler as ArangoCollectionHandler;
use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;
use LaravelFreelancerNL\Aranguent\Query\Builder as QueryBuilder;
use LaravelFreelancerNL\Aranguent\Concerns\DetectsLostConnections;
use LaravelFreelancerNL\Aranguent\Schema\Builder as SchemaBuilder;
use LaravelFreelancerNL\Aranguent\Query\Grammars\Grammar as QueryGrammar;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as FluentAQL;
use triagens\ArangoDb\ViewHandler as ArangoViewHandler;

class Connection extends IlluminateConnection
{
    use DetectsDeadlocks,
        DetectsLostConnections,
        ManagesTransactions;

    /**
     * {@inheritdoc}
     *
     * @var array
     */
    protected $defaultConfig = [
        ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://localhost:8529',
        ArangoConnectionOptions::OPTION_CONNECTION  => 'Keep-Alive',
        ArangoConnectionOptions::OPTION_AUTH_USER => null,
        ArangoConnectionOptions::OPTION_AUTH_PASSWD => null,
        'tablePrefix' => '',
    ];

    protected $config;

    protected $arangoConnection;

    protected $readArangoConnection;

    protected $reconnector;

    protected $database;

    protected $schemaGrammar;

    protected $queryGrammar;

    protected $pretending;

    protected $recordsModified;

    protected $loggingQueries;

    protected $queryLog;

    protected $collectionHandler;

    protected $viewHandler;

    protected $documentHandler;

    protected $graphHandler;

    protected $userHandler;

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
     * @throws Exception
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->defaultConfig, $config);

        if (isset($this->config ['database'])) {
            $this->database = $this->config ['database'];
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
     * @return \LaravelFreelancerNL\Aranguent\Schema\Builder
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
     * @return \LaravelFreelancerNL\Aranguent\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar;
    }

    /**
     * Get the default post processor instance.
     *
     * @return \LaravelFreelancerNL\Aranguent\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Run a select statement against the database and returns a generator.
     * ($useReadPdo is a dummy to adhere to the interface).
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @param array|null $transactionCollections
     * @return Iterator|null
     * @throws Exception
     */
    public function cursor($query, $bindings = [], $useReadPdo = null, $transactionCollections = null)
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($transactionCollections) {
            if ($this->pretending()) {
                return [];
            }
            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query, $bindings, $transactionCollections);

                return [];
            }

            $statement = new Statement($this->arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

            return $statement->execute();

        });
    }

    /**
     * Execute an AQL statement and return the boolean result.
     *
     * @param  string|FluentAQL  $query
     * @param  array   $bindings
     * @param  array|null   $transactionCollections
     * @return bool
     */
    public function statement($query, $bindings = [], $transactionCollections = null)
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

            $statement = new Statement($this->arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

            $cursor = $statement->execute();

            $affectedDocumentCount = $cursor->getWritesExecuted();
            $this->recordsHaveBeenModified($changed = $affectedDocumentCount > 0);

            return $changed;
        });
    }

    /**
     * Run an AQL statement and get the number of rows affected.
     *
     * @param  string|FluentAQL  $query
     * @param  array   $bindings
     * @param  array|null   $transactionCollections
     * @return int
     */
    public function affectingStatement($query, $bindings = [], $transactionCollections = null)
    {
        if ($query instanceof FluentAQL) {
            $bindings = $query->binds;
            $transactionCollections = $query->collections;
            $query = $query->query;
        }

        return $this->run($query, $bindings, function ($query, $bindings) use ($transactionCollections) {
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
            $statement = new Statement($this->arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

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
     * @param array|null $collections
     * @return bool
     * @throws Exception
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

            $statement = new Statement($this->arangoConnection, ['query' => $query, 'bindVars' => []]);

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
     * @param array $bindings
     * @return array
     * @throws Exception
     */
    public function explain($query, $bindings = [])
    {
        $statement = new Statement($this->arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

        return $statement->explain();
    }

    /**
     * Run a select statement against the database.
     *
     * @param string|FluentAQL $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @param null|array $transactionCollections
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true, $transactionCollections = null)
    {
        return $this->execute($query, $bindings, $useReadPdo, $transactionCollections);
    }

    /**
     * Run an AQL query against the database and return the results.
     *
     * @param string|FluentAQL $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @param null|array $transactionCollections
     * @return array
     */
    public function execute($query, $bindings = [], $useReadPdo = true, $transactionCollections = null)
    {
        if ($query instanceof FluentAQL) {
            $bindings = $query->binds;
            $transactionCollections = $query->collections;
            $query = $query->query;
        }

        return $this->run($query, $bindings, function ($query, $bindings, $transactionCollections= null) {
            if ($this->pretending()) {
                return [];
            }
            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query, $bindings, $transactionCollections);

                return [];
            }

            $statement = new Statement($this->arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

            $cursor = $statement->execute();

            return $cursor->getAll();
        });
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string|FluentAQL  $query
     * @param  array   $bindings
     * @param  array|null   $transactionCollections
     * @return bool
     */
    public function insert($query, $bindings = [], $transactionCollections = null)
    {
        return $this->statement($query, $bindings, $transactionCollections);
    }

    /**
     * Run an update statement against the database.
     *
     * @param  string|FluentAQL  $query
     * @param  array   $bindings
     * @param  array|null   $transactionCollections
     * @return int
     */
    public function update($query, $bindings = [], $transactionCollections = null)
    {

        return $this->affectingStatement($query, $bindings, $transactionCollections);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @param  array|null   $transactionCollections
     * @return int
     */
    public function delete($query, $bindings = [], $transactionCollections = null)
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
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
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

    public function getCollectionHandler()
    {
        if (!isset($this->collectionHandler)) {
            $this->collectionHandler = new ArangoCollectionHandler($this->arangoConnection);
        }

        return $this->collectionHandler;
    }

    public function getDocumentHandler()
    {
        if (!isset($this->documentHandler)) {
            $this->documentHandler = new ArangoDocumentHandler($this->arangoConnection);
        }
        return $this->documentHandler;
    }

    public function getUserHandler()
    {
        if (!isset($this->userHandler)) {
            $this->userHandler = new ArangoUserHandler($this->arangoConnection);
        }
        return $this->userHandler;
    }

    public function getGraphHandler()
    {
        if (!isset($this->graphHandler)) {
            $this->graphHandler = new ArangoGraphHandler($this->arangoConnection);
        }
        return $this->graphHandler;
    }

    public function getViewHandler()
    {
        if (!isset($this->viewHandler)) {
            $this->viewHandler = new ArangoViewHandler($this->arangoConnection);
        }
        return $this->viewHandler;
    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    public function getDatabase()
    {
        return $this->database;
    }
}
