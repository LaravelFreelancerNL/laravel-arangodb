<?php

namespace LaravelFreelancerNL\Aranguent\Schema;

use ArangoDBClient\ClientException;
use ArangoDBClient\Exception;
use ArangoDBClient\View;
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

    protected $collectionHandler;

    protected $graphHandler;

    protected $viewHandler;

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

        $this->collectionHandler = $connection->getCollectionHandler();

        $this->graphHandler = $connection->getGraphHandler();

        $this->viewHandler = $connection->getViewHandler();
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

        return new Blueprint($collection, $this->collectionHandler, $callback, $prefix);
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
        $this->collection($collection, $callback);
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
     * @param string $collection
     *
     * @throws Exception
     *
     * @return void
     */
    public function drop($collection)
    {
        $this->collectionHandler->drop($collection);
    }

    public function dropAllCollections()
    {
        $collections = $this->getAllCollections();

        foreach ($collections as $key => $collection) {
            $this->collectionHandler->drop($collection['name']);
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
        if ($collections = $this->hasCollection($collection)) {
            $this->drop($collection);
        }
    }

    /**
     * Get all of the collection names for the database.
     *
     * @param array $options
     *
     * @throws ClientException
     * @throws Exception
     *
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
     * Alias for getAllCollections().
     *
     * @param array $options
     *
     * @throws ClientException
     * @throws Exception
     *
     * @return array
     */
    protected function getAllTables(array $options = [])
    {
        return $this->getAllCollections($options);
    }

    /**
     * @param string $collection
     *
     * @throws Exception
     *
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
     *
     * @throws Exception
     *
     * @return bool
     */
    public function rename($from, $to)
    {
        return $this->collectionHandler->rename($from, $to);
    }

    /**
     * Determine if the given collection exists.
     *
     * @param string $collection
     *
     * @throws Exception
     *
     * @return bool
     */
    public function hasCollection(string $collection)
    {
        return $this->collectionHandler->has($collection);
    }

    /**
     * Alias for hasCollection.
     *
     * @param string $table
     *
     * @throws Exception
     *
     * @return bool
     */
    public function hasTable($table)
    {
        return $this->hasCollection($table);
    }

    /**
     * Check if any document in the collection has the attribute.
     *
     * @param string $collection
     * @param string $attribute
     *
     * @return bool
     */
    public function hasAttribute($collection, $attribute)
    {
        if (is_string($attribute)) {
            $attribute = [$attribute];
        }

        return $this->hasAttributes($collection, $attribute);
    }

    /**
     * Check if any document in the collection has the attribute.
     *
     * @param string $collection
     * @param array  $attributes
     *
     * @return bool
     */
    public function hasAttributes($collection, $attributes)
    {
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
     * @param string $table
     * @param string $column
     *
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        return $this->hasAttribute($table, $column);
    }

    /**
     * Alias for hasAttributes.
     *
     * @param string $table
     * @param array  $columns
     *
     * @return bool
     */
    public function hasColumns($table, $columns)
    {
        return $this->hasAttributes($table, $columns);
    }

    /**
     * get information about the collection.
     *
     * @param $collection
     *
     * @return mixed
     */
    public function getCollectionInfo($collection)
    {
        return $this->figures($collection);
    }

    /**
     * @param string $name
     * @param array  $properties
     * @param string $type
     *
     * @throws ClientException
     * @throws Exception
     */
    public function createView($name, array $properties, $type = 'arangosearch')
    {
        $view = new View($name, $type);

        $this->viewHandler->create($view);
        $this->viewHandler->setProperties($view, $properties);
    }

    /**
     * @param string $name
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function getView(string $name)
    {
        return $this->viewHandler->properties($name);
    }

    /**
     * @param string $name
     * @param array  $properties
     *
     * @throws ClientException
     * @throws Exception
     */
    public function editView($name, array $properties)
    {
        $this->viewHandler->setProperties($name, $properties);
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @throws Exception
     */
    public function renameView(string $from, string $to)
    {
        $this->viewHandler->rename($from, $to);
    }

    /**
     * @param string $name
     *
     * @throws ClientException
     * @throws Exception
     */
    public function dropView(string $name)
    {
        $this->viewHandler->drop($name);
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
