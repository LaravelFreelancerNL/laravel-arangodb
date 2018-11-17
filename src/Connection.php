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
use LaravelFreelancerNL\Aranguent\Concerns\DetectsLostConnections;
use LaravelFreelancerNL\Aranguent\Schema\Builder as SchemaBuilder;

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
        ArangoConnectionOptions::OPTION_DATABASE => null,
        ArangoConnectionOptions::OPTION_AUTH_USER => null,
        ArangoConnectionOptions::OPTION_AUTH_PASSWD => null,
        'tablePrefix' => '',
    ];

    protected $config;

    protected $arangoConnection;

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
        // First we will setup the default properties. We keep track of the DB
        // name we are connected to since it is needed when some reflective
        // type commands are run such as checking whether a table exists.
        $this->config = array_merge($this->defaultConfig, $config);

        $this->database = $this->config ['database'];

        $this->tablePrefix = $this->config['tablePrefix'];

        // activate and set the database client connection
        $this->arangoConnection = new ArangoConnection($this->config);

        $this->collectionHandler = new ArangoCollectionHandler($this->arangoConnection);
        $this->documentHandler = new ArangoDocumentHandler($this->arangoConnection);
        $this->graphHandler = new ArangoGraphHandler($this->arangoConnection);
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
     * Run a select statement against the database and returns a generator.
     * ($useReadPdo is a dummy to adhere to the interface).
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return \Iterator
     */
    public function cursor($query, $bindings = [], $useReadPdo = null)
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, $bindings, function ($query, $bindings) use ($arangoConnection) {
            if ($this->pretending()) {
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
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, $bindings, function ($query, $bindings) use ($arangoConnection) {
            if ($this->pretending()) {
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
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, $bindings, function ($query, $bindings) use ($arangoConnection) {
            if ($this->pretending()) {
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
     * @return bool
     */
    public function unprepared($query)
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, [], function ($query) use ($arangoConnection) {
            if ($this->pretending()) {
                return true;
            }

            $statement = new Statement($arangoConnection, ['query' => $query, 'bindVars' => []]);

            $cursor = $statement->execute();

            $affectedDocumentCount = $cursor->getWritesExecuted();

            $this->recordsHaveBeenModified($change = $affectedDocumentCount > 0);

            return $change;
        });
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $arangoConnection = $this->arangoConnection;

        return $this->run($query, $bindings, function ($query, $bindings) use ($arangoConnection) {
            if ($this->pretending()) {
                return [];
            }
            $statement = new Statement($arangoConnection, ['query' => $query, 'bindVars' => $bindings]);

            $cursor = $statement->execute();

            return $cursor->getAll();
        });
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

    //ArangoDB functions

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

    public function getGraphHandler()
    {
        return $this->graphHandler;
    }
}
