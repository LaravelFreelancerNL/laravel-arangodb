<?php

namespace LaravelFreelancerNL\Aranguent\Schema;

use Closure;
use Illuminate\Database\Schema\Builder as IlluminateBuilder;
use LogicException;
use LaravelFreelancerNL\Aranguent\Connection;

class Builder extends IlluminateBuilder
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
     * @param  \LaravelFreelancerNL\Aranguent\Connection  $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();
        $this->collectionHandler = $connection->getCollectionHandler();
    }

    /**
     * Modify a collection on the schema.
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
     * Modify a table on the schema.
     *
     * @param  string    $collection
     * @param  \Closure  $callback
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->collection($table, $callback);
    }

    /**
     * Create a new table on the schema.
     *
     * @param  string    $collection
     * @param  \Closure  $callback
     * @return void
     */
    public function create($collection, Closure $callback)
    {
        $this->build(tap($this->createBlueprint($collection), function ($blueprint) use ($callback) {
            $blueprint->create();

            $callback($blueprint);
        }));
    }

    /**
     * Determine if the given collection exists.
     *
     * @param  string  $collection
     * @return bool
     */
    public function hasCollection($collection)
    {
        return $this->collectionHandler->has($collection);
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        return $this->hasCollection($table);
    }

    /**
     * Drop a collection from the schema.
     *
     * @param  string  $table
     * @return void
     */
    public function drop($collection)
    {
        $this->build(tap($this->createBlueprint($collection), function ($blueprint) {
            $blueprint->drop();
        }));
    }

    public function dropAllCollections()
    {
        $collections = $this->getAllCollections();

        if (empty($collections)) {
            return;
        }

        foreach ($collections as $key => $name) {
            $this->collectionHandler->drop($name);
        }
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $this->dropAllCollections();

    }

    /**
     * Get all of the collection names for the database.
     *
     * @return array
     */
    protected function getAllCollections()
    {
        return $this->collectionHandler->getAllCollections(['excludeSystem' => true]);
    }

    /**
     * Get all of the table names for the database.
     *
     * @return array
     */
    protected function getAllTables()
    {
        return $this->getAllCollections();
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function dropAllViews()
    {
        throw new LogicException('This database driver does not support dropping all views.');
    }

    /**
     * Rename a table on the schema.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function rename($from, $to)
    {
        $this->build(tap($this->createBlueprint($from), function ($blueprint) use ($to) {
            $blueprint->rename($to);
        }));
    }

     /**
     * Execute the blueprint to build / modify the table.
     *
     * @param  \LaravelFreelancerNL\Aranguent\Schema\Blueprint  $blueprint
     * @return void
     */
    protected function build(Blueprint $blueprint)
    {
        $blueprint->build($this->connection, $this->grammar);
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

        return new Blueprint($collection, $callback, $prefix);
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
     * Set the database connection instance.
     *
     * @param  \LaravelFreelancerNL\Aranguent\Connection  $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
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
     * Irrelevant placeholders as ArangoDB is schemaless.
     * These functions return the most appropriate response for a smooth transition.
     */

    /**
     * Determine if the given table has a given column.
     *
     * @param  string  $table
     * @param  string  $column
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        return true;
    }

    /**
     * Get the data type for the given column name.
     *
     * @param  string  $table
     * @param  string  $column
     * @return string
     */
    public function getColumnType($table, $column)
    {
        return '';
    }

    /**
     * Get the column listing for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getColumnListing($table)
    {
        return [];
    }

    /**
     * Enable foreign key constraints.
     *
     * @return bool
     */
    public function enableForeignKeyConstraints()
    {
        return true;
    }

    /**
     * Disable foreign key constraints.
     *
     * @return bool
     */
    public function disableForeignKeyConstraints()
    {
        return true;
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param  string  $table
     * @param  array   $columns
     * @return bool
     */
    public function hasColumns($table, array $columns)
    {
        return true;
    }

}
