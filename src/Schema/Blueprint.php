<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Schema;

use ArangoClient\Schema\SchemaManager;
use Closure;
use Illuminate\Support\Fluent;
use Illuminate\Support\Traits\Macroable;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\Columns;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\Indexes;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\Tables;

/**
 * Class Blueprint.
 *
 * The Schema blueprint works differently from the standard Illuminate version:
 * 1) ArangoDB is schemaless: we don't need to (and can't) create columns
 * 2) ArangoDB doesn't allow DB schema actions within AQL nor within a transaction.
 *
 * This means that:
 * 1) We catch column related methods silently for backwards compatibility and ease of migrating between DB types
 * 2) We don't need to compile AQL for transactions within the accompanying schema grammar. (for now)
 * 3) We can just execute each command on order. We will gather them first for possible future optimisations.
 */
class Blueprint
{
    use Macroable;
    use Tables;
    use Columns;
    use Indexes;

    /**
     * The connection that is used by the blueprint.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * The grammar that is used by the blueprint.
     *
     * @var Grammar
     */
    protected $grammar;

    /**
     * The table the blueprint describes.
     *
     * @var string
     */
    protected $table;

    /**
     * The handler for table manipulation.
     */
    protected SchemaManager $schemaManager;

    /**
     * The prefix of the table.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The commands that should be run for the table.
     *
     * @var Fluent[]
     */
    protected $commands = [];

    /**
     * Catching columns to be able to add fluent indexes.
     *
     * @var array
     */
    protected $columns = [];


    /**
     * the key generator to use for this table.
     *
     * @var bool
     */
    protected $keyGenerator = 'traditional';

    protected int $incrementOffset = 0;

    /**
     * Create a new schema blueprint.
     *
     * Blueprint constructor.
     *
     * @param string $table
     * @param Grammar $grammar
     * @param SchemaManager $schemaManager
     * @param Closure|null $callback
     * @param string $prefix
     */
    public function __construct($table, $grammar, $schemaManager, Closure $callback = null, $prefix = '')
    {
        $this->table = $table;

        $this->grammar = $grammar;

        $this->schemaManager = $schemaManager;

        $this->prefix = $prefix;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Execute the blueprint against the database.
     *
     *
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar = null)
    {
        $this->connection = $connection;

        if (!isset($grammar)) {
            $this->grammar = $connection->getSchemaGrammar();
        }

        foreach ($this->commands as $command) {
            if ($command->handler === 'aql') {
                $command = $this->compileAqlCommand($command);
            }

            $this->executeCommand($command);
        }
    }

    /**
     * Generate the compilation method name and call it if method exists in the Grammar object.
     */
    public function compileAqlCommand(Fluent $command): Fluent
    {
        $compileMethod = 'compile' . ucfirst($command->name);
        if (method_exists($this->grammar, $compileMethod)) {
            return $this->grammar->$compileMethod($this->table, $command);
        }

        return $command;
    }

    /**
     * Generate the execution method name and call it if the method exists.
     */
    public function executeCommand(Fluent $command): void
    {
        $executeNamedMethod = 'execute' . ucfirst($command->name) . 'Command';

        if (method_exists($this, $executeNamedMethod)) {
            $this->$executeNamedMethod($command);

            return;
        }

        $this->executeCommandByHandler($command);
    }

    protected function executeCommandByHandler(Fluent $command): void
    {
        if (!isset($command->handler)) {
            return;
        }
        $executeHandlerMethod = 'execute' . ucfirst($command->handler) . 'Command';
        if (method_exists($this, $executeHandlerMethod)) {
            $this->$executeHandlerMethod($command);
        }
    }

    /**
     * Execute an AQL statement.
     */
    public function executeAqlCommand(Fluent $command)
    {
        $this->connection->statement($command->aqb->query, $command->aqb->binds);
    }

    public function executeCollectionCommand(Fluent $command): void
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery('/* ' . $command->explanation . " */\n", []);

            return;
        }

        if (method_exists($this->schemaManager, $command->method)) {
            $this->schemaManager->{$command->method}($command);
        }
    }

    /**
     * Solely provides feedback to the developer in pretend mode.
     */
    public function executeIgnoreCommand(Fluent $command): void
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery('/* ' . $command->explanation . " */\n", []);
        }
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param  string  $name
     * @return Fluent
     */
    protected function addCommand($name, array $parameters = [])
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    /**
     * Create a new Fluent command.
     *
     * @param  string  $name
     * @return Fluent
     */
    protected function createCommand($name, array $parameters = [])
    {
        return new Fluent(array_merge(compact('name'), $parameters));
    }

    /**
     * Get the commands on the blueprint.
     *
     * @return Fluent[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    public function from(int $startingValue)
    {
        $this->incrementOffset = $startingValue;

        $info['method'] = 'from';
        $info['explanation'] = "The autoincrement offset has been set to {$startingValue}.";

        return $this->addCommand('ignore', $info);
    }

    /**
     * Silently catch unsupported schema methods. Store columns for backwards compatible fluent index creation.
     *
     * @param  array<mixed>  $args
     * @return Blueprint
     */
    public function __call($method, $args = [])
    {
        $columnMethods = [
            'bigIncrements', 'bigInteger', 'binary', 'boolean', 'char', 'date', 'dateTime', 'dateTimeTz', 'decimal',
            'double', 'enum', 'engine', 'float', 'foreignId', 'geometry', 'geometryCollection', 'increments', 'integer',
            'ipAddress', 'json', 'jsonb', 'lineString', 'longText', 'macAddress', 'mediumIncrements', 'mediumInteger',
            'mediumText', 'morphs', 'uuidMorphs', 'multiLineString', 'multiPoint', 'multiPolygon',
            'nullableMorphs', 'nullableUuidMorphs', 'nullableTimestamps', 'point', 'polygon', 'rememberToken',
            'set', 'smallIncrements', 'smallInteger', 'softDeletes', 'softDeletesTz', 'string',
            'text', 'time', 'timeTz', 'timestamp', 'timestampTz', 'timestamps', 'tinyIncrements', 'tinyInteger',
            'unsignedBigInteger', 'unsignedDecimal', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger',
            'unsignedTinyInteger', 'uuid', 'year',
        ];

        if (in_array($method, $columnMethods)) {
            if (isset($args[0]) && is_string($args[0])) {
                $this->columns[] = $args[0];
            }
        }

        $autoIncrementMethods = ['increments', 'autoIncrement'];
        if (in_array($method, $autoIncrementMethods)) {
            $this->setKeyGenerator('autoincrement');
        }

        if ($method === 'uuid') {
            $this->setKeyGenerator('uuid');
        }

        $this->ignoreMethod($method);

        return $this;
    }

    protected function setKeyGenerator(string $generator = 'traditional'): void
    {
        $column = end($this->columns);
        if ($column === '_key' || $column === 'id') {
            $this->keyGenerator = $generator;
        }
    }

    protected function ignoreMethod(string $method)
    {
        $info = [];
        $info['method'] = $method;
        $info['explanation'] = "'$method' is ignored; Aranguent Schema Blueprint doesn't support it.";
        $this->addCommand('ignore', $info);
    }

    public function renameIdField(mixed $fields)
    {
        return array_map(function ($value) {
            return $value === 'id' ? '_key' : $value;
        }, $fields);
    }
}
