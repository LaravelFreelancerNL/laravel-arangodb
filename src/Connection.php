<?php

namespace LaravelFreelancerNL\Aranguent;

use ArangoDBClient\Statement;
use ArangoDBClient\Connection as ArangoConnection;
use ArangoDBClient\GraphHandler as ArangoGraphHandler;
use Illuminate\Database\Connection as IlluminateConnection;
use ArangoDBClient\DocumentHandler as ArangoDocumentHandler;
use LaravelFreelancerNL\Aranguent\Concerns\DetectsDeadlocks;
use LaravelFreelancerNL\Aranguent\Concerns\ManagesTransactions;
use ArangoDBClient\CollectionHandler as ArangoCollectionHandler;
use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;
use LaravelFreelancerNL\Aranguent\Query\Builder as QueryBuilder;
use LaravelFreelancerNL\Aranguent\Concerns\DetectsLostConnections;
use LaravelFreelancerNL\Aranguent\Query\Processors\Processor;
use LaravelFreelancerNL\Aranguent\Schema\Builder as SchemaBuilder;
use LaravelFreelancerNL\Aranguent\Query\Grammars\Grammar as QueryGrammar;

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

    protected $collectionPrefix;

    protected $schemaGrammar;

    protected $queryGrammar;

    protected $pretending;

    protected $recordsModified;

    protected $loggingQueries;

    protected $queryLog;

    protected $collectionHandler;

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

        $this->collectionHandler = new ArangoCollectionHandler($this->arangoConnection);
        $this->documentHandler = new ArangoDocumentHandler($this->arangoConnection);
        $this->graphHandler = new ArangoGraphHandler($this->arangoConnection);

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
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @param  array|null  $transactionCollections
     * @return \Iterator|null
     */
    public function cursor($query, $bindings = [], $useReadPdo = null, $transactionCollections = null)
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, $bindings, function ($query, $bindings) use ($arangoConnection, $transactionCollections) {
            if ($this->pretending()) {
                return [];
            }
            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query, $bindings, $transactionCollections);
                return [];
            }

            $statement = new Statement($arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

            $cursor = $statement->execute();

            return $cursor;
        });
    }

    /**
     * Execute an AQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @param  array|null   $transactionCollections
     * @return bool
     */
    public function statement($query, $bindings = [], $transactionCollections = null)
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, $bindings, function ($query, $bindings) use ($arangoConnection, $transactionCollections) {
            if ($this->pretending()) {
                return true;
            }
            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query, $bindings, $transactionCollections);
                return true;
            }

            $statement = new Statement($arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

            $cursor = $statement->execute();

            $affectedDocumentCount = $cursor->getWritesExecuted();
            $this->recordsHaveBeenModified($changed = $affectedDocumentCount > 0);

            return $changed;
        });
    }

    /**
     * Run an AQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @param  array|null   $transactionCollections
     * @return int
     */
    public function affectingStatement($query, $bindings = [], $transactionCollections = null)
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, $bindings, function ($query, $bindings) use ($arangoConnection, $transactionCollections) {
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
            $statement = new Statement($arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

            $cursor = $statement->execute();

            $affectedDocumentCount = $cursor->getWritesExecuted();

            $this->recordsHaveBeenModified($affectedDocumentCount > 0);

            return $affectedDocumentCount;
        });
    }

    /**
     * Run a raw, unprepared query against the connection.
     *
     * @param  string  $query
     * @param  array|null $collections
     * @return bool
     */
    public function unprepared($query)
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, [], function ($query) use ($arangoConnection) {
            if ($this->pretending()) {
                return true;
            }
            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query);
                return [];
            }

            $statement = new Statement($arangoConnection, ['query' => $query, 'bindVars' => []]);

            $cursor = $statement->execute();

            $affectedDocumentCount = $cursor->getWritesExecuted();

            $this->recordsHaveBeenModified($change = $affectedDocumentCount > 0);

            return $change;
        });
    }

    /**
     * Returns the query execution plan. The query will not be executed.
     *
     * @param  string  $query
     * @param  array $bindings
     * @return array
     */
    public function explain($query, $bindings = [])
    {
        $statement = new Statement($this->arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

        $explanation = $statement->explain();

        return $explanation;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @param  null|array  $transactionCollections
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true, $transactionCollections = null)
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, $bindings, function ($query, $bindings) use ($arangoConnection, $transactionCollections) {
            if ($this->pretending()) {
                return [];
            }
            if ($this->transactionLevel() > 0) {
                $this->addQueryToTransaction($query, $bindings);

                return [];
            }

            $statement = new Statement($arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

            $cursor = $statement->execute();

            return $cursor->getAll();
        });
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
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
     * @param  string  $query
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
     * @return \LaravelFreelancerNL\Aranguent\Query\Builder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Begin a fluent query against a database collection.
     *
     * @param  string  $collection
     * @return \LaravelFreelancerNL\Aranguent\Query\Builder
     */
    public function collection($collection)
    {
        return $this->query()->from($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function table($table)
    {
        return $this->collection($table);
    }



    /**
     * Get the collection prefix for the connection.
     *
     * @return string
     */
    public function getCollectionPrefix()
    {
        return $this->collectionPrefix;
    }

    /**
     * Get the collection prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->getCollectionPrefix();
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
        return $this->collectionHandler;
    }

    public function getDocumentHandler()
    {
        return $this->documentHandler;
    }

    public function setUserHandler($userHandler)
    {
        $this->userHandler = $userHandler;
    }

    public function getUserHandler()
    {
        return $this->userHandler;
    }

    public function setGraphHandler($graphHandler)
    {
        $this->graphHandler = $graphHandler;
    }

    public function getGraphHandler()
    {
        return $this->graphHandler;
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