<?php

namespace LaravelFreelancerNL\Aranguent\Schema;

use Closure;

class ArangoBuilder extends Builder
{
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
     * @param  string    $table
     * @param  \Closure  $callback
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->collection($collection, $callback);
    }

    /**
     * Modify a table on the schema.
     *
     * @param  string    $table
     * @param  \Closure  $callback
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->build($this->createBlueprint($table, $callback));
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

    //Irrelevant placeholders as ArangoDB is schemaless.
    //These functions return the most appropriate response for compatibility.

    /**
     * Get the column listing for a given table.
     * ArangoDB is schemaless, a column listing is not available
     *
     * @param  string  $table
     * @return array
     */
    public function getColumnListing($table)
    {
        return [];
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
        return true;
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
