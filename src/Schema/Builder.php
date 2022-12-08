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
use LaravelFreelancerNL\Aranguent\QueryException;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\UsesBlueprints;

class Builder extends \Illuminate\Database\Schema\Builder
{
    use UsesBlueprints;

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
     * Create a new database Schema manager.
     *
     * Builder constructor.
     *
     * @param  Connection  $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->grammar = $connection->getSchemaGrammar();

        $this->schemaManager = $connection->getArangoClient()->schema();
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        return $this->handleExceptionsAsQueryExceptions(function () use ($table) {
            return $this->schemaManager->hasCollection($table);
        });
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
     *
     * @throws ArangoException
     */
    public function getAllTables(): array
    {
        return $this->schemaManager->getCollections(true);
    }

    /**
     * Rename a table (collection).
     *
     * @param  string  $from
     * @param  string  $to
     *
     * @throws ArangoException
     */
    public function rename($from, $to): bool
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
        $parameters['columns'] = $columns;

        $command = new Fluent($parameters);
        $compilation = $this->grammar->compileHasColumn($table, $command);

        return $this->connection->statement($compilation['aql']);
    }

    /**
     * @param  array<mixed>  $properties
     *
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
     * @throws ArangoException
     */
    public function getView(string $name): \stdClass
    {
        return $this->schemaManager->getView($name);
    }

    public function hasView(string $view): bool
    {
        return $this->handleExceptionsAsQueryExceptions(function () use ($view) {
            return $this->schemaManager->hasView($view);
        });
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
     *
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
     *
     * @throws ArangoException
     */
    public function dropView(string $name)
    {
        $this->schemaManager->deleteView($name);
    }

    /**
     * @param  string  $name
     * @return bool
     *
     * @throws ArangoException
     */
    public function dropViewIfExists(string $name): bool
    {
        if ($this->hasView($name)) {
            $this->schemaManager->deleteView($name);
        }

        return true;
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
     * @throws ArangoException
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
     * @throws ArangoException
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
     * @throws QueryException
     */
    protected function handleExceptionsAsQueryExceptions(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            throw new QueryException($e->getMessage(), [], $e);
        }
    }

    /**
     * Silently catch the use of unsupported builder methods.
     */
    public function __call(string $method, mixed $args): void
    {
        Log::warning("The Aranguent Schema Builder doesn't support method '$method'\n");
    }
}
