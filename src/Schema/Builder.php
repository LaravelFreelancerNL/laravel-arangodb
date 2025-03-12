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
use LaravelFreelancerNL\Aranguent\Schema\Concerns\HandlesIndexes;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\HandlesIndexNaming;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\HandlesGraphs;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\HandlesViews;
use LaravelFreelancerNL\Aranguent\Schema\Concerns\UsesBlueprints;

class Builder extends \Illuminate\Database\Schema\Builder
{
    use HandlesAnalyzers;
    use HandlesIndexNaming;
    use HandlesGraphs;
    use HandlesIndexes;
    use HandlesViews;
    use UsesBlueprints;

    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected $connection;

    public SchemaManager $schemaManager;

    /**
     * The schema grammar instance.
     *
     * @var Grammar
     */
    public $grammar;


    /**
     * index prefixes?
     */
    public ?bool $prefixIndexes;

    /**
     * The table prefix.
     */
    public string $prefix;

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

        $this->prefixIndexes = $this->connection->getConfig('prefix_indexes');

        $this->prefix = $this->prefixIndexes
            ? $this->connection->getConfig('prefix')
            : '';

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
     * @param string $name
     * @return array<mixed>
     *
     * @throws ArangoException
     */
    public function getTable($name): array
    {
        return (array) $this->schemaManager->getCollectionStatistics($name);
    }

    /**
     * Get all the tables for the database; including ArangoDB system tables
     *
     * @return array<mixed>
     *
     * @throws ArangoException
     */
    public function getAllTables(): array
    {
        return $this->mapResultsToArray(
            $this->schemaManager->getCollections(false),
        );
    }

    /**
     * Get the tables that belong to the database.
     *
     * @param string|string[]|null $schema
     * @return array<mixed>
     * @throws ArangoException
     */
    public function getTables($schema = null)
    {
        unset($schema);

        return $this->mapResultsToArray(
            $this->schemaManager->getCollections(true),
        );
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
        $collections = $this->getTables(true);

        foreach ($collections as $name) {
            $this->schemaManager->deleteCollection($name['name']);
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
     * @return array<mixed>
     */
    public function getColumns($table)
    {
        $compilation = $this->grammar->compileColumns(null, $table);

        $rawColumns = $this->connection->select($compilation['aqb'], $compilation['bindings']);

        return $this->mapResultsToArray($rawColumns);
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
     * Disable foreign key constraints during the execution of a callback.
     *
     * ArangoDB doesn't have foreign keys so this is just a dummy to keep things working
     * for functionality that expect this method.
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    public function withoutForeignKeyConstraints(Closure $callback)
    {
        return $callback();
    }

    /**
     * @param mixed[] $results
     * @return mixed[]
     */
    protected function mapResultsToArray($results)
    {
        return array_map(function ($result) { return (array) $result; }, $results);
    }

    /**
     * Silently catch the use of unsupported builder methods.
     */
    public function __call($method, $parameters)
    {
        Log::warning("The ArangoDB driver's schema builder doesn't support method '$method'\n");
    }
}
