<?php

namespace LaravelFreelancerNL\Aranguent\Schema;

use ArangoClient\Exceptions\ArangoException;
use ArangoClient\Schema\SchemaManager;
use Closure;
use Illuminate\Support\Fluent;
use LaravelFreelancerNL\Aranguent\Connection;
use stdClass;

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
     * @param array<mixed> $config
     */
    public function create(string $table, Closure $callback, array $config = []): void
    {
        $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($callback, $config) {
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
     * Drop a table (collection) from the schema.
     *
     * @throws ArangoException
     */
    public function drop(string $table)
    {
        $this->schemaManager->deleteCollection($table);
    }

    /**
     * Drop all tables (collections) from the schema.
     *
     * @throws ArangoException
     */
    public function dropAllTables(): void
    {
        $collections = $this->getAllTables();

        foreach ($collections as $name) {
            $this->schemaManager->deleteCollection($name->name);
        }
    }

    /**
     * @throws ArangoException
     */
    public function dropIfExists(string $table): void
    {
        $tableExists = $this->hasTable($table);
        if ($tableExists) {
            $this->drop($table);
        }
    }

    /**
     * Get all the tables for the database; excluding ArangoDB system collections
     *
     * @return array<mixed>
     * @throws ArangoException
     */
    public function getAllTables(): array
    {
        return $this->schemaManager->getCollections(true);
    }

    /**
     * @throws ArangoException
     */
    protected function getTable(string $table): stdClass
    {
        return $this->schemaManager->getCollection($table);
    }

    /**
     * Rename a table (collection).
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
     * Determine if the given table (collection) exists.
     *
     * @throws ArangoException
     */
    public function hasTable(string $table): bool
    {
        return $this->schemaManager->hasCollection($table);
    }

    /**
     * Check if any row (document) in the table (collection) has the attribute.
     *
     * @param  string  $table
     * @param  mixed  $column
     *
     * @return bool
     */
    public function hasColumn(string $table, $column): bool
    {
        if (is_string($column)) {
            $column = [$column];
        }

        return $this->hasColumns($table, $column);
    }

    /**
     * Check if any document in the collection has the attribute.
     *
     * @param  array<mixed>  $columns
     */
    public function hasColumns(string $table, array $columns): bool
    {
        $parameters = [];
        $parameters['name'] = 'hasAttribute';
        $parameters['handler'] = 'aql';
        $parameters['attribute'] = $columns;

        $command = new Fluent($parameters);
        $compilation = $this->grammar->compileHasAttribute($table, $command);

        return $this->connection->statement($compilation['aql']);
    }

    /**
     * @param  array<mixed> $properties
     * @throws ArangoException
     */
    public function createView(string $name, array $properties, string $type = 'arangosearch')
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
     * Drop all views from the schema.
     *
     * @throws ArangoException
     */
    public function dropAllViews(): void
    {
        $views = $this->schemaManager->getViews();

        foreach ($views as $view) {
            $this->schemaManager->deleteView($view->name);
        }
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
