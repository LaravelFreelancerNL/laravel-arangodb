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
use LaravelFreelancerNL\FluentAQL\QueryBuilder as ArangoQueryBuilder;
use LogicException;
use RuntimeException;
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
     */
    protected string $driverName = 'arangodb';

    /**
     * Connection constructor.
     *
     * @param  array<mixed>  $config
     *
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
     */
    public function getSchemaBuilder(): SchemaBuilder
    {
//        if (!isset($this->schemaGrammar)) {
//            $this->useDefaultSchemaGrammar();
//        }

        return new SchemaBuilder($this);
    }

    /**
     * Get the default query grammar instance.
     */
    //    protected function getDefaultQueryGrammar(): QueryGrammar
    //    {
    //        return new QueryGrammar();
    //    }

    /**
     * Get the default post processor instance.
     */
    protected function getDefaultPostProcessor(): Processor
    {
        return new Processor();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return QueryGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        ($grammar = new QueryGrammar())->setConnection($this);

        return $grammar;
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
            return call_user_func($this->reconnector, $this);
        }
    }

    /**
     * Reconnect to the database if an ArangoDB connection is missing.
     *
     * @return void
     */
    public function reconnectIfMissingConnection()
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
     * Set the name of the connected database.
     *
     * @param  string  $database
     * @return $this
     */
    public function setDatabaseName($database)
    {
        $this->database = $database;

        if ($this->arangoClient !== null) {
            $this->arangoClient->setDatabase($database);
        }

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

    /**
     * Escape a binary value for safe SQL embedding.
     *
     * @param  string  $value
     * @return string
     */
    protected function escapeBinary($value)
    {
        if (mb_detect_encoding($value, ['UTF-8'])) {
            return $value;
        }

        return base64_encode($value);
    }

    /**
     * Escape a value for safe SQL embedding.
     *
     * @param  array<mixed>|string|float|int|bool|null  $value
     * @param  bool  $binary
     * @return string
     */
    public function escape($value, $binary = false)
    {
        if ($value === null) {
            return 'null';
        } elseif (is_string($value) && $binary === true) {
            return $this->escapeBinary($value);
        } elseif (is_int($value) || is_float($value)) {
            return (string) $value;
        } elseif (is_bool($value)) {
            return $this->escapeBool($value);
        } elseif (is_array($value)) {
            return $this->escapeArray($value);
        } else {
            if (str_contains($value, "\00")) {
                throw new RuntimeException('Strings with null bytes cannot be escaped. Use the binary escape option.');
            }

            if (preg_match('//u', $value) === false) {
                throw new RuntimeException('Strings with invalid UTF-8 byte sequences cannot be escaped.');
            }

            return $this->escapeString($value);
        }
    }

    /**
     * Escape an array value for safe SQL embedding.
     *
     * @param  array<mixed>  $array
     * @return string
     */
    protected function escapeArray(array $array): string
    {
        foreach($array as $key => $value) {
            $array[$key] = $this->escape($value);
        }

        if (array_is_list($array)) {
            return '[' . implode(', ', $array) . ']';
        }

        $grammar = $this->getDefaultQueryGrammar();
        return $grammar->generateAqlObject($array);
    }

    /**
     * Escape a boolean value for safe SQL embedding.
     *
     * @param  bool  $value
     * @return string
     */
    protected function escapeBool($value)
    {
        return $value ? 'true' : 'false';
    }

    /**
     * Escape a string value for safe SQL embedding.
     *
     * @param  string  $value
     * @return string
     */
    protected function escapeString($value)
    {
        return '"' . str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $value
        ) . '"';
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int|float  $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }
}
