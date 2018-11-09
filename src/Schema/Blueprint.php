<?php

namespace LaravelFreelancerNL\Aranguent\Schema;

use Closure;
use Illuminate\Database\Schema\Blueprint as IlluminateBlueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;
use Illuminate\Support\Traits\Macroable;


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
     * The collection the blueprint describes.
     *
     * @var string
     */
    protected $collection;

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
     * Whether to make the collection temporary.
     *
     * @var bool
     */
    public $temporary = false;

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
     * @param Connection $connection
     * @param $grammar
     */
    public function build(Connection $connection, $grammar)
    {
        //Gather commands
        // Execute commands
    }

    /**
     * Alias for toAql
     * Only used now for the pretend migration option to show a list of commands to run.
     *
     * @param Connection $connection
     * @param $grammar
     */
    public function toSql(Connection $connection, $grammar)
    {
        //Call some kind of commandList method
    }

    /**
     * Get all of the commands matching the given names.
     *
     * @param  array  $names
     * @return \Illuminate\Support\Collection
     */
    protected function commandsNamed(array $names)
    {
        return collect($this->commands)->filter(function ($command) use ($names) {
            return in_array($command->name, $names);
        });
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
     * @return \Illuminate\Support\Fluent
     */
    public function create()
    {
        return $this->addCommand('create');
    }

    /**
     * Indicate that the collection needs to be temporary.
     *
     * @return void
     */
    public function temporary()
    {
        $this->temporary = true;
    }

    /**
     * Indicate that the collection should be dropped.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function drop()
    {
        return $this->addCommand('drop');
    }

    /**
     * Indicate that the collection should be dropped if it exists.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function dropIfExists()
    {
        return $this->addCommand('dropIfExists');
    }

    /**
     * Indicate that the given attributes should be dropped.
     *
     * @param  array|mixed  $attributes
     * @return \Illuminate\Support\Fluent
     */
    public function dropAttribute($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        return $this->addCommand('dropAttribute', compact('attributes'));
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
        return $this->addCommand('renameAttribute', compact('from', 'to'));
    }

    /**
     * Indicate that the given primary key should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropPrimary($index = null)
    {
        return $this->dropIndexCommand('dropPrimary', 'primary', $index);
    }

    /**
     * Indicate that the given unique key should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropUnique($index)
    {
        return $this->dropIndexCommand('dropUnique', 'unique', $index);
    }

    /**
     * Indicate that the given index should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropIndex($index)
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    /**
     * Indicate that the given spatial index should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropSpatialIndex($index)
    {
        return $this->dropIndexCommand('dropSpatialIndex', 'spatialIndex', $index);
    }


    /**
     * Indicate that the given indexes should be renamed.
     *
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function renameIndex($from, $to)
    {
        return $this->addCommand('renameIndex', compact('from', 'to'));
    }

    /**
     * Indicate that the timestamp attributes should be dropped.
     *
     * @return void
     */
    public function dropTimestamps()
    {
        $this->dropAttribute('created_at', 'updated_at');
    }

    /**
     * Indicate that the timestamp attributes should be dropped.
     *
     * @return void
     */
    public function dropTimestampsTz()
    {
        $this->dropTimestamps();
    }

    /**
     * Indicate that the soft delete attribute should be dropped.
     *
     * @param  string  $attribute
     * @return void
     */
    public function dropSoftDeletes($attribute = 'deleted_at')
    {
        $this->dropAttribute($attribute);
    }

    /**
     * Indicate that the soft delete attribute should be dropped.
     *
     * @param  string  $attribute
     * @return void
     */
    public function dropSoftDeletesTz($attribute = 'deleted_at')
    {
        $this->dropSoftDeletes($attribute);
    }

    /**
     * Indicate that the remember token attribute should be dropped.
     *
     * @return void
     */
    public function dropRememberToken()
    {
        $this->dropAttribute('remember_token');
    }

    /**
     * Indicate that the polymorphic attributes should be dropped.
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function dropMorphs($name, $indexName = null)
    {
        $this->dropIndex($indexName ?: $this->createIndexName('index', ["{$name}_type", "{$name}_id"]));

        $this->dropAttribute("{$name}_type", "{$name}_id");
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
     * Specify the primary key(s) for the collection.
     *
     * @param  string|array  $attributes
     * @param  string  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function primary($attributes, $name = null, $algorithm = null)
    {
        return $this->indexCommand('primary', $attributes, $name, $algorithm);
    }

    /**
     * Specify a unique index for the collection.
     *
     * @param  string|array  $attributes
     * @param  string  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function unique($attributes, $name = null, $algorithm = null)
    {
        return $this->indexCommand('unique', $attributes, $name, $algorithm);
    }

    /**
     * Specify an index for the collection.
     *
     * @param  string|array  $attributes
     * @param  string  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function index($attributes, $name = null, $algorithm = null)
    {
        return $this->indexCommand('index', $attributes, $name, $algorithm);
    }

    /**
     * Specify a spatial index for the collection.
     *
     * @param  string|array  $attributes
     * @param  string  $name
     * @return \Illuminate\Support\Fluent
     */
    public function spatialIndex($attributes, $name = null)
    {
        return $this->indexCommand('spatialIndex', $attributes, $name);
    }


    /**
     * Add a new index command to the blueprint.
     *
     * @param  string  $type
     * @param  string|array  $attributes
     * @param  string  $index
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    protected function indexCommand($type, $attributes, $index, $algorithm = null)
    {
        $attributes = (array) $attributes;

        // If no name was specified for this index, we will create one using a basic
        // convention of the collection name, followed by the attributes, followed by an
        // index type, such as primary or index, which makes the index unique.
        $index = $index ?: $this->createIndexName($type, $attributes);

        return $this->addCommand(
            $type, compact('index', 'attributes', 'algorithm')
        );
    }

    /**
     * Create a new drop index command on the blueprint.
     *
     * @param  string  $command
     * @param  string  $type
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    protected function dropIndexCommand($command, $type, $index)
    {
        $attributes = [];

        // If the given "index" is actually an array of attributes, the developer means
        // to drop an index merely by specifying the attributes involved without the
        // conventional name, so we will build the index name from the attributes.
        if (is_array($index)) {
            $index = $this->createIndexName($type, $attributes = $index);
        }

        return $this->indexCommand($command, $attributes, $index);
    }

    /**
     * Create a default index name for the collection.
     *
     * @param  string  $type
     * @param  array  $attributes
     * @return string
     */
    protected function createIndexName($type, array $attributes)
    {
        $index = strtolower($this->prefix.$this->collection.'_'.implode('_', $attributes).'_'.$type);

        return str_replace(['-', '.'], '_', $index);
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
     * Get the attributes on the blueprint that should be added.
     *
     * @return \Illuminate\Database\Schema\AttributeDefinition[]
     */
    public function getAddedAttributes()
    {
        return array_filter($this->attributes, function ($attribute) {
            return ! $attribute->change;
        });
    }

    /**
     * Silently catch unsupported schema methods
     *
     * @param $method
     * @param $args
     * @return Blueprint
     */
    public function __call($method, $args)
    {
        // Dummy.
        error_log("Aranguent Schema Blueprint doesn't support method '$method'\n");
        return $this;
    }
}
