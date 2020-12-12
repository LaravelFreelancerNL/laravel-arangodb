<?php

namespace LaravelFreelancerNL\Aranguent\Schema\Concerns;

use ArangoDBClient\Exception;
use Illuminate\Support\Fluent;

trait Indexes
{
    /**
     * Add a new index command to the blueprint.
     *
     * @param string            $type
     * @param string|array|null $columns
     * @param $name
     * @param array $indexOptions
     *
     * @return Fluent
     */
    protected function indexCommand($type = '', $columns = null, $name = null, $indexOptions = [])
    {
        if ($type == '') {
            $type = $this->mapIndexType('persistent');
        }

        if ($columns === null) {
            $columns = end($this->columns);
        }

        if (is_string($columns)) {
            $columns = [$columns];
        }

        $indexOptions['name'] = $name ?: $this->createIndexName($type, $columns, $indexOptions);

        return $this->addCommand('index', compact('type', 'columns', 'indexOptions'));
    }

    /**
     * Specify an index for the table.
     *
     * @param string|array $columns
     * @param string       $name
     * @param string|null  $algorithm
     *
     * @return Fluent
     */
    public function index($columns = null, $name = null, $algorithm = null)
    {
        $type = $this->mapIndexType($algorithm);

        return $this->indexCommand($type, $columns, $name);
    }

    /**
     * Create a hash index for fast exact matching.
     *
     * @param null  $columns
     * @param array $indexOptions
     *
     * @return Fluent
     */
    public function hashIndex($columns = null, $indexOptions = [])
    {
        return $this->indexCommand('hash', $columns, $indexOptions);
    }

    /**
     * @param null|string $column
     * @param $name
     * @param array $indexOptions
     *
     * @return Fluent
     */
    public function fulltextIndex($column = null, $name = null, $indexOptions = [])
    {
        return $this->indexCommand('fulltext', $column, $name, $indexOptions);
    }

    /**
     *  Specify a spatial index for the table.
     *
     * @param $columns
     * @param null  $name
     * @param array $indexOptions
     *
     * @return Fluent
     */
    public function geoIndex($columns, $name = null, $indexOptions = [])
    {
        return $this->indexCommand('geo', $columns, $name, $indexOptions);
    }

    /**
     * Specify a spatial index for the table.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Fluent
     */
    public function spatialIndex($columns, $name = null)
    {
        return $this->geoIndex($columns, $name);
    }

    /**
     * @param $columns
     * @param string|null $name
     * @param array       $indexOptions
     *
     * @return Fluent
     */
    public function skiplistIndex($columns, $name = null, $indexOptions = [])
    {
        return $this->indexCommand('skiplist', $columns, $name, $indexOptions);
    }

    public function persistentIndex($columns, $name = null, $indexOptions = [])
    {
        return $this->indexCommand('persistent', $columns, $name, $indexOptions);
    }

    /**
     * Create a TTL index for the table.
     *
     * @param $columns
     * @param null  $name
     * @param array $indexOptions
     *
     * @return Fluent
     */
    public function ttlIndex($columns, $name = null, $indexOptions = [])
    {
        return $this->indexCommand('ttl', $columns, $name, $indexOptions);
    }

    /**
     * Specify a unique index for the table.
     *
     * @param string|array $columns
     * @param string       $name
     * @param string|null  $algorithm
     *
     * @return Fluent
     */
    public function unique($columns = null, $name = null, $algorithm = null)
    {
        $type = $this->mapIndexType($algorithm);

        $indexOptions = [];
        $indexOptions['unique'] = true;

        return $this->indexCommand($type, $columns, $name, $indexOptions);
    }

    /**
     * @param $command
     *
     * @throws Exception
     */
    public function executeIndexCommand($command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery('/* ' . $command->explanation . " */\n", []);

            return;
        }

        $options = [
            'type'    => $command->type,
            'fields'  => $command->columns,
            'unique'  => $command->unique,
            'options' => $command->indexOptions,
        ];

        if (isset($command->indexOptions) && is_array($command->indexOptions)) {
            $options = array_merge($options, $command->indexOptions);
        }

        $this->collectionHandler->createIndex($this->table, $options);
    }

    /**
     * Indicate that the given index should be dropped.
     *
     * @param $name
     *
     * @return Fluent
     */
    public function dropIndex($name)
    {
        $parameters = [];
        $parameters['name'] = 'dropIndex';
        $parameters['index'] = $name;
        $parameters['explanation'] = "Drop the '" . $name . "' index on the {$this->table} table.";
        $parameters['handler'] = 'collection';

        return $this->addCommand('dropIndex', $parameters);
    }

    /**
     * Drop the index by first getting all the indexes on the table; then selecting the matching one
     * by type and columns.
     *
     * @param $command
     */
    public function executeDropIndexCommand($command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery('/* ' . $command->explanation . " */\n", []);

            return;
        }
        $this->collectionHandler->dropIndex($this->table, $command->index);
    }

    /**
     * @param string|null $algorithm
     *
     * @return mixed|string
     */
    protected function mapIndexType($algorithm)
    {
        $typeConversion = [
            'HASH'  => 'hash',
            'BTREE' => 'persistent',
            'RTREE' => 'geo',
            'TTL'   => 'ttl',
        ];
        $algorithm = strtoupper($algorithm);

        return (isset($typeConversion[$algorithm])) ? $typeConversion[$algorithm] : 'persistent';
    }

    /**
     * Create a default index name for the table.
     *
     * @param string $type
     * @param array  $columns
     * @param array  $options
     *
     * @return string
     */
    public function createIndexName($type, array $columns, array $options = [])
    {
        $nameParts = [];
        $nameParts[] = $this->prefix . $this->table;
        $nameParts = array_merge($nameParts, $columns);
        $nameParts[] = $type;
        $nameParts = array_merge($nameParts, array_keys($options));
        array_filter($nameParts);

        $index = strtolower(implode('_', $nameParts));
        $index = preg_replace("/\[\*+\]+/", '_array', $index);

        return preg_replace('/[^A-Za-z0-9]+/', '_', $index);
    }
}
