<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Schema;

use ArangoClient\Exceptions\ArangoException;
use ArangoClient\Schema\SchemaManager;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Fluent;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Exceptions\QueryException;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\HandlesAnalyzers;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\HandlesViews;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\UsesBlueprints;

class Builder extends \Illuminate\Database\Schema\Builder
{
    use HandlesAnalyzers;
    use HandlesViews;
    use UsesBlueprints;

    const EDGE_COLLECTION = 3;

    /**
     * The database connection instance.
     *
     * @var Connection
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
     * @param string|string[] $column
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        return $this->hasColumns($table, $column);
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param string $table
     * @param string|string[] $columns
     * @return bool
     */
    public function hasColumns($table, $columns)
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        $parameters = [];
        $parameters['name'] = 'hasColumn';
        $parameters['handler'] = 'aql';
        $parameters['columns'] = $columns;

        $command = new Fluent($parameters);

        $compilation = $this->grammar->compileHasColumn($table, $command);
        return $this->connection->select($compilation['aqb'])[0];
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
            throw new QueryException($this->connection->getName(), $e->getMessage(), [], $e);
        }
    }

    /**
     * Silently catch the use of unsupported builder methods.
     */
    public function __call($method, $parameters)
    {
        Log::warning("The ArangoDB driver's schema builder doesn't support method '$method'\n");
    }
}
