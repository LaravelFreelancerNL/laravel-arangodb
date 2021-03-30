<?php

namespace LaravelFreelancerNL\Aranguent\Schema;

use ArangoClient\Exceptions\ArangoException;
use ArangoClient\Schema\SchemaManager;
use Closure;
use Illuminate\Support\Fluent;
use LaravelFreelancerNL\Aranguent\Connection;

class Builder
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    protected SchemaManager $schemaManager;

    /**
     * The schema grammar instance.
     *
     * @var Grammar
     */
    protected $grammar;

    /**
     * The Blueprint resolver callback.
     *
     * @var \Closure
     */
    protected $resolver;

    /**
     * Create a new database Schema manager.
     *
     * Builder constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->grammar = $connection->getSchemaGrammar();

        $this->schemaManager = $connection->getArangoClient()->schema();
    }

    /**
     * Create a new collection on the schema.
     *
     * @param string  $collection
     * @param Closure $callback
     * @param array   $config
     *
     * @return void
     */
    public function create($collection, Closure $callback, $config = [])
    {
        $this->build(tap($this->createBlueprint($collection), function ($blueprint) use ($callback, $config) {
            $blueprint->create($config);

            $callback($blueprint);
        }));
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param string        $collection
     * @param \Closure|null $callback
     *
     * @return Blueprint
     */
    protected function createBlueprint($collection, Closure $callback = null)
    {
        //Prefixes are unnamed in ArangoDB
        $prefix = null;

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $collection, $callback, $prefix);
        }

        return new Blueprint($collection, $this->schemaManager, $callback, $prefix);
    }

    protected function build(Blueprint $blueprint)
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * @param Closure $resolver
     *
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Alias for table.
     *
     * @param string  $collection
     * @param Closure $callback
     *
     * @return void
     */
    public function collection($collection, Closure $callback)
    {
        $this->table($collection, $callback);
    }

    /**
     * Modify a table's schema.
     *
     * @param string  $table
     * @param Closure $callback
     *
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->build($this->createBlueprint($table, $callback));
    }

    /**
     * Drop a collection from the schema.
     *
     * @param  string  $collection
     *
     * @return void
     */
    public function drop($collection)
    {
        $this->schemaManager->deleteCollection($collection);
    }

    public function dropAllCollections()
    {
        $collections = $this->getAllCollections();

        foreach ($collections as $name) {
            $this->schemaManager->deleteCollection($name['name']);
        }
    }

    /**
     * Alias for dropAllCollections.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $this->dropAllCollections();
    }

    /**
     * @param $collection
     *
     * @throws Exception
     */
    public function dropIfExists($collection)
    {
        $collections = $this->hasCollection($collection);
        if ($collections) {
            $this->drop($collection);
        }
    }

    /**
     * Get all of the collection names for the database.
     *
     * @param  array  $options
     *
     * @return array
     */
    public function getAllCollections(array $options = [])
    {
        return $this->schemaManager->getCollections(true);
    }

    /**
     * Get all of the table names for the database.
     * Alias for getAllCollections().
     *
     * @param  array  $options
     *
     * @return array
     */
    protected function getAllTables(array $options = [])
    {
        return $this->getAllCollections($options);
    }

    /**
     * @param  string  $collection
     *
     * @return array
     * @throws ArangoException
     */
    protected function getCollection($collection)
    {
        return $this->schemaManager->getCollection($collection);
    }

    /**
     * Rename a collection.
     *
     * @param $from
     * @param $to
     *
     * @return bool
     * @throws ArangoException
     *
     */
    public function rename($from, $to)
    {
        return (bool) $this->schemaManager->renameCollection($from, $to);
    }

    /**
     * Determine if the given collection exists.
     *
     * @param  string  $collection
     *
     * @return bool
     * @throws ArangoException
     */
    public function hasCollection(string $collection): bool
    {
        return $this->schemaManager->hasCollection($collection);
    }

    /**
     * Alias for hasCollection.
     *
     * @param  string  $table
     *
     *
     * @return bool
     * @throws ArangoException
     */
    public function hasTable($table): bool
    {
        return $this->hasCollection($table);
    }

    /**
     * Check if any document in the collection has the attribute.
     *
     * @param  string  $collection
     * @param  mixed  $attribute
     *
     * @return bool
     */
    public function hasAttribute(string $collection, $attribute): bool
    {
        if (is_string($attribute)) {
            $attribute = [$attribute];
        }

        return $this->hasAttributes($collection, $attribute);
    }

    /**
     * Check if any document in the collection has the attribute.
     *
     * @param  string  $collection
     * @param  array  $attributes
     *
     * @return bool
     */
    public function hasAttributes(string $collection, array $attributes)
    {
        $parameters = [];
        $parameters['name'] = 'hasAttribute';
        $parameters['handler'] = 'aql';
        $parameters['attribute'] = $attributes;

        $command = new Fluent($parameters);
        $compilation = $this->grammar->compileHasAttribute($collection, $command);

        return $this->connection->statement($compilation['aql']);
    }

    /**
     * Alias for hasAttribute.
     *
     * @param  string  $table
     * @param  string  $column
     *
     * @return bool
     */
    public function hasColumn(string $table, string $column): bool
    {
        return $this->hasAttribute($table, $column);
    }

    /**
     * Alias for hasAttributes.
     *
     * @param  string  $table
     * @param  array  $columns
     *
     * @return bool
     */
    public function hasColumns(string $table, array $columns): bool
    {
        return $this->hasAttributes($table, $columns);
    }

    /**
     * @param  string  $name
     * @param  array  $properties
     * @param  string  $type
     * @throws ArangoException
     */
    public function createView(string $name, array $properties, $type = 'arangosearch')
    {
        $view = $properties;
        $view['name'] = $name;
        $view['type'] = $type;

        $this->schemaManager->createView($view);
    }

    /**
     * @param  string  $name
     *
     * @return mixed
     * @throws ArangoException
     */
    public function getView(string $name)
    {
        return $this->schemaManager->getView($name);
    }

    /**
     * @param  string  $name
     * @param  array  $properties
     * @throws ArangoException
     */
    public function editView(string $name, array $properties)
    {
        $this->schemaManager->updateView($name, $properties);
    }

    /**
     * @param  string  $from
     * @param  string  $to
     *
     * @throws ArangoException
     */
    public function renameView(string $from, string $to)
    {
        $this->schemaManager->renameView($from, $to);
    }

    /**
     * @param  string  $name
     * @throws ArangoException
     */
    public function dropView(string $name)
    {
        $this->schemaManager->deleteView($name);
    }

    /**
     * Get the database connection instance.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Silently catch the use of unsupported builder methods.
     *
     * @param $method
     * @param $args
     *
     * @return null
     */
    public function __call($method, $args)
    {
        error_log("The Aranguent Schema Builder doesn't support method '$method'\n");
    }
}
