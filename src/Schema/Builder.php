<?php

namespace LaravelFreelancerNL\Aranguent\Schema;

use Closure;
use LaravelFreelancerNL\Aranguent\Connection;
use Illuminate\Database\Schema\Builder as IlluminateBuilder;
use Illuminate\Database\Schema\Blueprint as IlluminateBlueprint;

class Builder
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    protected $collectionHandler;

    /**
     * The schema grammar instance.
     *
     * @var \LaravelFreelancerNL\Aranguent\Schema\Grammars\Grammar
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
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->grammar = $connection->getSchemaGrammar();

        $this->collectionHandler = $connection->getCollectionHandler();
    }

    /**
     * Create a new collection on the schema.
     *
     * @param  string    $collection
     * @param  \Closure  $callback
     * @param  array $config
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
     * @param  string  $collection
     * @param  \Closure|null  $callback
     * @return \LaravelFreelancerNL\Aranguent\Schema\Blueprint
     */
    protected function createBlueprint($collection, Closure $callback = null)
    {
        $prefix = $this->connection->getConfig('prefix_indexes')
            ? $this->connection->getConfig('prefix')
            : '';

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $collection, $callback, $prefix);
        }

        return new Blueprint($collection, $this->collectionHandler, $callback, $prefix);
    }

    /**
     */
    protected function build(Blueprint $blueprint)
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Modify a collection's schema.
     *
     * @param  string    $collection
     * @param  \Closure  $callback
     * @return void
     */
    public function collection($collection, Closure $callback)
    {
        $this->build($this->createBlueprint($collection, $callback));
    }

    /**
     * Alias for collection.
     *
     * @param  string    $table
     * @param  \Closure  $callback
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->collection($table, $callback);
    }

    /**
     * Drop a collection from the schema.
     *
     * @param  string  $collection
     * @return void
     */
    public function drop($collection)
    {
        $this->collectionHandler->drop($collection);
    }

    public function dropAllCollections()
    {
        $collections = $this->getAllCollections();

        foreach ($collections as $key => $name) {
            $this->collectionHandler->drop($name);
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
     */
    public function dropIfExists($collection)
    {
        if ($collections = $this->hasCollection($collection)) {
            $this->drop($collection);
        }
    }

    /**
     * Get all of the collection names for the database.
     *
     * @param array $options
     * @return array
     */
    protected function getAllCollections(array $options = [])
    {
        if (!isset($options['excludeSystem'])) {
            $options['excludeSystem'] = true;
        }
        return $this->collectionHandler->getAllCollections($options);
    }
    /**
     * Get all of the table names for the database.
     * Alias for getAllCollections()
     *
     * @param array $options
     * @return array
     */
    protected function getAllTables(array $options = [])
    {
        return $this->getAllCollections($options);
    }

    /**
     * @param string $collection
     * @return \ArangoDBClient\Collection
     */
    protected function getCollection($collection)
    {
        return $this->collectionHandler->get($collection);
    }

    /**
     * Rename a collection.
     *
     * @param $from
     * @param $to
     * @return bool
     */
    public function rename($from, $to)
    {
        return $this->collectionHandler->rename($from, $to);
    }

    /**
     * Determine if the given collection exists.
     *
     * @param  string  $collection
     * @return bool
     */
    public function hasCollection(string $collection)
    {
        return $this->collectionHandler->has($collection);
    }
    /**
     * Alias for hasCollection
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        return $this->hasCollection($table);
    }

    /**
     * Check if any document in the collection has the attribute
     * TODO: Should we keep this?
     *
     * @param string $collection
     * @param string $attribute
     * @return bool
     */
    public function hasAttribute($collection, $attribute)
    {
        $this->build(tap($this->createBlueprint($collection), function ($blueprint) use ($attribute) {
            return $blueprint->hasAttribute($attribute);
        }));
    }
    /**
     * Alias for hasAttribute
     *
     * @param  string  $table
     * @param  string  $column
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        return $this->hasAttribute($table, $column);
    }

    /**
     * get information about the collection
     *
     * @param $collection
     * @return mixed
     */
    public function getCollectionInfo($collection)
    {
        return $this->figures($collection);
    }


    /**
     * Get the database connection instance.
     *
     * @return \LaravelFreelancerNL\Aranguent\Connection
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
     * @return null
     */
    public function __call($method, $args)
    {
        error_log("The Aranguent Schema Builder doesn't support method '$method'\n");

        return null;
    }
}
