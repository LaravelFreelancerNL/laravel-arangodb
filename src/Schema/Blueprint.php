<?php

namespace LaravelFreelancerNL\Aranguent\Schema;

use Closure;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Database\Schema\Blueprint as IlluminateBlueprint;
use Illuminate\Database\Schema\Grammars\Grammar as IlluminateGrammar;


/**
 * Class Blueprint
 *
 * The Schema blueprint works differently from the standard Illuminate version:
 * 1) ArangoDB is schemaless: we don't need to create columns
 * 2) ArangoDB doesn't allow DB schema actions within AQL nor within a transaction.
 * 3) ArangoDB transactions aren't optimized for large scale data handling as the entire transaction has to fit in main memory.
 *
 * This means that:
 * 1) We catch column related methods silently for backwards compatibility and ease of migrating from one DB type to another
 * 2) We don't need to compile AQL for transactions within the accompanying schema grammar. (for now)
 * 3) We can just execute each command on order. We will gather them first for possible future optimisations.
 *
 * @package LaravelFreelancerNL\Aranguent\Schema
 */
class Blueprint
{
    use Macroable;

    /**
     * The connection that is used by the blueprint.
     *
     * @var \Illuminate\Database\Connection  $connection
     */
    protected $connection;

    /**
     * The grammar that is used by the blueprint.
     *
     * @var \LaravelFreelancerNL\Aranguent\Schema\Grammars\Grammar  $grammar
     */
    protected $grammar;

    /**
     * The collection the blueprint describes.
     *
     * @var string
     */
    protected $collection;

    /**
     * The handler for collection manipulation.
     *
     */
    protected $collectionHandler;

    /**
     * The prefix of the collection.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The commands that should be run for the collection.
     *
     * @var \Illuminate\Support\Fluent[]
     */
    protected $commands = [];

    /**
     * Catching attributes to be able to add fluent indexes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Whether to make the collection temporary.
     *
     * @var bool
     */
    public $temporary = false;

    /**
     * Detect if _key (and thus proxy _id) should autoincrement
     *
     * @var bool
     */
    protected $autoIncrement = false;

    /**
     * Create a new schema blueprint.
     *
     * Blueprint constructor.
     * @param string $collection
     * @param \ArangoDBClient\CollectionHandler $collectionHandler
     * @param Closure|null $callback
     * @param string $prefix
     */
    public function __construct($collection, $collectionHandler, Closure $callback = null, $prefix = '')
    {
        $this->collection = $collection;

        $this->collectionHandler = $collectionHandler;

        $this->prefix = $prefix;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    public function build(Connection $connection, IlluminateGrammar $grammar)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();

        foreach ($this->commands as $command) {
            if ($command->handler == 'aql') {
                $command = $this->compileAqlCommand($command);
            }

            $this->executeCommand($command);
        }
    }

    /**
     * Generate the compilation method name and call it if method exists in the Grammar object.
     *
     * @param $command
     * @return mixed
     */
    public function compileAqlCommand($command)
    {
        $compileMethod = 'compile' . ucfirst($command->name);
        if (method_exists($this->grammar, $compileMethod)) {
            return $this->grammar->$compileMethod($command);
        }
    }

    /**
     * Generate the execution method name and call it if the method exists.
     *
     * @param $command
     */
    public function executeCommand($command)
    {
        $executeNamedMethod = 'execute' . ucfirst($command->name) . 'Command';
        $executeHandlerMethod = 'execute' . ucfirst($command->handler) . 'Command';
        if (method_exists($this, $executeNamedMethod)) {
            $this->$executeNamedMethod($command);
        } else if (method_exists($this, $executeHandlerMethod)) {
            $this->$executeHandlerMethod($command);
        }
    }

    /**
     * Execute an AQL statement
     *
     * @param $command
     */
    public function executeAqlCommand($command)
    {
        $this->connection->statement($command->aql['query'], $command->aql['bindings']);
    }


    public function executeCollectionCommand($command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery("/* " . $command->explanation . " */\n", []);
            return null;
        }

        if (method_exists($this->collectionHandler, $command->method)) {
            $this->collectionHandler->{$command->method}($command->parameters);
        }
    }

    public function executeIndexCommand($command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery("/* " . $command->explanation . " */\n", []);
            return null;
        }

        $this->collectionHandler->index($this->collection, $command->type, $command->attributes, $command->unique,$command->indexOptions);
    }

    /**
     * Solely provides feedback to the developer in pretend mode.
     *
     * @param $command
     * @return null
     */
    public function executeIgnoreCommand($command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery("/* " . $command->explanation . " */\n", []);
            return null;
        }
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * @return bool
     */
    protected function creating()
    {
        return collect($this->commands)->contains(function ($command) {
            return $command->name === 'create';
        });
    }

    /**
     * Indicate that the collection needs to be created.
     *
     * @param array $config
     * @return \Illuminate\Support\Fluent
     */
    public function create($config = [])
    {
        $parameters['config'] = $config;
        $parameters['explanation'] = "Create '{$this->collection}' collection.";
        $parameters['handler'] = 'collection';

        return $this->addCommand('create', $parameters);
    }

    public function executeCreateCommand($command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery("/* " . $command->explanation . " */\n", []);
            return null;
        }
        $config = $command->config;
        if ($this->temporary === true) {
            $config['isVolatile'] = true;
        }
        if ($this->autoIncrement === true) {
            $config['keyOptions']['autoincrement'] = true;
        }
        $this->collectionHandler->create($this->collection, $config);
    }


    /**
     * Indicate that the collection should be dropped.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function drop()
    {
        $parameters['explanation'] = "Drop the '{$this->collection}' collection.";
        $parameters['handler'] = 'collection';
        return $this->addCommand('drop', $parameters);
    }

    /**
     * Indicate that the collection should be dropped if it exists.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function dropIfExists()
    {
        $parameters['explanation'] = "Drop the '{$this->collection}' collection.";
        $parameters['handler'] = 'collection';
        return $this->addCommand('dropIfExists');
    }

    /**
     * Indicate that the given attribute(s) should be dropped.
     *
     * @param  array|mixed  $attributes
     * @return \Illuminate\Support\Fluent
     */
    public function dropAttribute($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $parameters['handler'] = 'aql';
        $parameters['attributes'] = $attributes;
        $parameters['explanation'] = "Drop the following attribute(s): " . implode(',', $attributes) . ".";
        return $this->addCommand('dropAttribute', compact('parameters'));
    }

    /**
     * Check if any document within the collection has the attribute.
     *
     * @param string $attribute
     * @return Fluent
     */
    public function hasAttribute($attribute)
    {
        $parameters['handler'] = 'aql';
        $parameters['explanation'] = "Checking if any document within the collection has the '$attribute' attribute.";
        $parameters['attribute'] = $attribute;

        return $this->addCommand('hasAttribute', $parameters);
    }

    /**
     * Indicate that the given attributes should be renamed.
     *
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function renameAttribute($from, $to)
    {
        $parameters['handler'] = 'aql';
        $parameters['explanation'] = "Rename the attribute '$from' to '$to'.";
        $parameters['from'] = $from;
        $parameters['to'] = $to;
        return $this->addCommand('renameAttribute', $parameters);
    }

    /**
     * Indicate that the given index should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropIndex($type, $attributes)
    {
//        return $this->dropIndexCommand('dropIndex', $type, $attributes);
    }

     /**
     * Rename the collection to a given name.
     *
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function rename($to)
    {
        return $this->addCommand('rename', compact('to'));
    }


    /**
     * Specify an index for the collection. Creates a skiplist index by default.
     *
     * @param string $type         - index type: hash, fulltext, geo, persistent, skiplist,
     * @param  string|array  $attributes
     * @param  array  $indexOptions
     * @return \Illuminate\Support\Fluent
     */
    public function index($attributes = null, $type = null, $indexOptions = [])
    {
        return $this->indexCommand($type, $attributes, $indexOptions);
    }

    public function hashIndex($attributes = null, $indexOptions = [])
    {
        return $this->indexCommand('hash', $attributes, $indexOptions);
    }

    public function fulltextIndex($attributes = null, $indexOptions = [])
    {
        return $this->indexCommand('fulltext', $attributes, $indexOptions);
    }

    /**
     *  Specify a spatial index for the collection.
     *
     * @param $attributes
     * @param array $indexOptions
     * @return Fluent
     */
    public function geoIndex($attributes, $indexOptions = [])
    {
        return $this->indexCommand('geo', $attributes, $indexOptions);
    }

    /**
     * Alias for the geoIndex
     *
     * @param  string|array  $attributes
     * @param  array  $indexOptions
     * @return \Illuminate\Support\Fluent
     */
    public function spatialIndex($attributes, $indexOptions = [])
    {
        return $this->geoIndex($attributes, $indexOptions);
    }

    public function persistentIndex($attributes, $indexOptions = [])
    {
        return $this->indexCommand('persistent', $attributes, $indexOptions);
    }

    public function skiplistIndex($attributes, $indexOptions = [])
    {
        return $this->indexCommand('skiplist', $attributes, $indexOptions);
    }

    /**
     * Specify a unique index for the collection.
     *
     * @param  string|array  $attributes
     * @param  array  $indexOptions
     * @return \Illuminate\Support\Fluent
     */
    public function unique($attributes = null, $indexOptions = [])
    {
        $indexOptions['unique'] = true;

        return $this->indexCommand('skiplist', $attributes, $indexOptions);
    }

    /**
     * Add a new index command to the blueprint.
     *
     * @param  string  $type
     * @param  string|array  $attributes
     * @param  array $indexOptions
     * @return \Illuminate\Support\Fluent
     */
    protected function indexCommand($type = '', $attributes = [], $indexOptions = [])
    {
        if ($type == '') {
            $type = 'skiplist';
        }

        if ($attributes === null) {
            $attributes = end($this->attributes);
        }
        $attributes = (array) $attributes;

        $unique = false;
        if (isset($indexOptions['unique'])) {
            $unique = $indexOptions['unique'];
            unset($indexOptions['unique']);
        }

        return $this->addCommand('index', compact('type', 'attributes', 'unique', 'indexOptions'));
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return \Illuminate\Support\Fluent
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
     * @param  array  $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function createCommand($name, array $parameters = [])
    {
        return new Fluent(array_merge(compact('name'), $parameters));
    }

    /**
     * Get the collection the blueprint describes.
     *
     * @return string
     */
    public function getCollection()
    {
        return $this->collection;
    }
    /**
     * Alias for getCollection.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->getCollection();
    }

    /**
     * Get the commands on the blueprint.
     *
     * @return \Illuminate\Support\Fluent[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Silently catch unsupported schema methods. Store attributes for backwards compatible fluent index creation.
     *
     * @param $method
     * @param $args
     * @return Blueprint
     */
    public function __call($method, $args)
    {
        $columnMethods = [
            'bigIncrements', 'bigInteger', 'binary', 'boolean', 'char', 'date', 'dateTime', 'dateTimeTz', 'decimal',
            'double', 'enum', 'float', 'geometry', 'geometryCollection', 'increments', 'integer', 'ipAddress', 'json',
            'jsonb', 'lineString', 'longText', 'macAddress', 'mediumIncrements', 'mediumInteger', 'mediumText',
            'multiLineString', 'multiPoint', 'multiPolygon', 'nullableTimestamps', 'point',
            'polygon', 'polygon',  'smallIncrements', 'smallInteger',  'string', 'text', 'time',
            'timeTz', 'timestamp', 'timestampTz', 'tinyIncrements', 'tinyInteger',
            'unsignedBigInteger', 'unsignedDecimal', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger',
            'unsignedTinyInteger', 'uuid', 'year'
        ];

        if (in_array($method, $columnMethods)) {
            $this->attributes[] = $args[0];
        }

        $autoIncrementMethods = ['increments', 'autoIncrement'];
        if (in_array($method, $autoIncrementMethods)) {
            $this->autoIncrement = true;
        }

        $info['method'] = $method;
        $info['explanation'] = "'$method' is ignored; Aranguent Schema Blueprint doesn't support it.";
        $this->addCommand('ignore', $info);

        return $this;
    }
}
