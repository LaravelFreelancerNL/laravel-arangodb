<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent;

use ArangoClient\ArangoClient;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Database\Schema\Grammars\Grammar as IlluminateGrammar;
use LaravelFreelancerNL\Aranguent\Concerns\DetectsDeadlocks;
use LaravelFreelancerNL\Aranguent\Concerns\DetectsLostConnections;
use LaravelFreelancerNL\Aranguent\Concerns\HandlesArangoDb;
use LaravelFreelancerNL\Aranguent\Concerns\ManagesTransactions;
use LaravelFreelancerNL\Aranguent\Concerns\RunsQueries;
use LaravelFreelancerNL\Aranguent\Query\Grammar as QueryGrammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use LaravelFreelancerNL\Aranguent\Schema\Builder as SchemaBuilder;
use LaravelFreelancerNL\Aranguent\Schema\Grammar;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as ArangoQueryBuilder;
use LogicException;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class Connection extends IlluminateConnection
{
    use HandlesArangoDb;
    use DetectsDeadlocks;
    use DetectsLostConnections;
    use ManagesTransactions;
    use RunsQueries;

    protected ?ArangoClient $arangoClient = null;

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
     * @throws UnknownProperties
     */
    public function __construct($config = [])
    {
        $this->config = $config;

        $this->database = (isset($this->config['database'])) ? $this->config['database'] : null;
        $this->tablePrefix = $this->config['tablePrefix'] ?? null;

        // activate and set the database client connection
        $this->arangoClient = new ArangoClient($this->config);

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so, we initialize these to their default values while starting.
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
     * Get the schema grammar used by the connection.
     *
     * @return IlluminateGrammar
     */
    public function getSchemaGrammar()
    {
        return $this->schemaGrammar;
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
        $this->arangoClient = null;
    }

    /**
     * Reconnect to the database.
     *
     * @throws \LogicException
     */
    public function reconnect()
    {
        if (is_callable($this->reconnector)) {
//            $this->arangoClient = null;

            $result = call_user_func($this->reconnector, $this);

            return $result;
        }

        throw new LogicException('Lost connection and no reconnector available.');
    }

    /**
     * Reconnect to the database if an ArangoDB connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->arangoClient)) {
            $this->reconnect();
        }
    }

    public function getArangoClient(): ArangoClient|null
    {
        return $this->arangoClient;
    }

    /**
     * @param  string|null  $database
     * @return $this
     */
    public function setDatabaseName($database)
    {
        $this->database = $database;
        $this->arangoClient->setDatabase($database);

        return $this;
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    public static function aqb(): ArangoQueryBuilder
    {
        return new ArangoQueryBuilder();
    }
}
