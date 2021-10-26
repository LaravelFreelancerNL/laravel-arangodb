<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Schema;

use ArangoClient\Exceptions\ArangoException;
use ArangoClient\Schema\SchemaManager;
use Closure;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Fluent;
use LaravelFreelancerNL\Aranguent\Connection;
use stdClass;

class Builder extends \Illuminate\Database\Schema\Builder
{
    /**
     * The database connection instance.
     *
     * @var IlluminateConnection
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
            return call_user_func($this->resolver, $collection, $this->schemaManager, $callback, $prefix);
        }

        return new Blueprint($collection, $this->schemaManager, $callback, $prefix);
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

    protected function build($blueprint)
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    /**
     * Create a new collection on the schema.
     *
     * @param array<mixed> $config
     */
    public function create($table, Closure $callback, array $config = []): void
    {
        $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($callback, $config) {
            $blueprint->create($config);

            $callback($blueprint);
        }));
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
     * Determine if the given table exists.
     *
     * @param string $table
     * @return bool
     * @throws ArangoException
     */
    public function hasTable($table)
    {
        return $this->schemaManager->hasCollection($table);
    }

    /**
     * @throws ArangoException
     */
    public function dropIfExists($table): void
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
     * Drop a table (collection) from the schema.
     *
     * @throws ArangoException
     */
    public function drop($table)
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
     * Determine if the given table has a given column.
     *
     * @param  string  $table
     * @param  string  $column
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        if (is_string($column)) {
            $column = [$column];
        }

        return $this->hasColumns($table, $column);
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param  string  $table
     * @param  array  $columns
     * @return bool
     */
    public function hasColumns($table, array $columns)
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
    public function getView(string $name): stdClass
    {
        return $this->schemaManager->getView($name);
    }

    /**
     * @throws ArangoException
     */
    public function getAllViews(): array
    {
        return $this->schemaManager->getViews();
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
     * Create a database in the schema.
     *
     * @param  string  $name
     * @return bool
     *
     * @throws \LogicException
     */
    public function createDatabase($name)
    {
        return $this->schemaManager->createDatabase($name);
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param  string  $name
     * @return bool
     *
     * @throws \LogicException
     */
    public function dropDatabaseIfExists($name)
    {
        if ($this->schemaManager->hasDatabase($name)) {
            return $this->schemaManager->deleteDatabase($name);
        }

        return true;
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
     */
    public function __call(string $method, mixed $args): void
    {
        Log::warning("The Aranguent Schema Builder doesn't support method '$method'\n");
    }
}
