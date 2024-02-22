<?php

namespace LaravelFreelancerNL\Aranguent\Schema\Concerns;

use ArangoClient\Exceptions\ArangoException;
use Illuminate\Support\Fluent;

trait Indexes
{
    /**
     * Add a new index command to the blueprint.
     *
     * @param  string|array<string>|null  $columns
     */
    protected function indexCommand(
        string $type,
        array|null|string $columns = null,
        string $name = null,
        array $indexOptions = []
    ): Fluent {
        if ($columns === null) {
            $columns = end($this->columns);
        }

        if (is_string($columns)) {
            $columns = [$columns];
        }

        $columns = $this->renameIdField($columns);

        $indexOptions['name'] = $name ?: $this->createIndexName($type, $columns, $indexOptions);

        return $this->addCommand('index', compact('type', 'columns', 'indexOptions'));
    }

    /**
     * Specify an index for the table.
     *
     * @param array|null|string $columns
     * @param null|string $name
     * @param null|string $algorithm
     * @param array<mixed> $indexOptions
     * @return Fluent
     */
    public function index($columns = null, $name = null, $algorithm = null, $indexOptions = [])
    {
        $type = $this->mapIndexAlgorithm($algorithm);

        return $this->indexCommand($type, $columns, $name, $indexOptions);
    }

    /**
     * Specify a spatial index for the table.
     *
     * @param string|array $columns
     * @param null|string $name
     * @param array<mixed> $indexOptions
     * @return Fluent
     */
    public function spatialIndex($columns, $name = null, $indexOptions = [])
    {
        return $this->indexCommand('geo', $columns, $name, $indexOptions);
    }

    /**
     *  Specify a spatial index for the table.
     *
     * @param  array<mixed>|null|string  $columns
     * @param  null|string  $name
     * @param  array<mixed>  $indexOptions
     * @return Fluent
     */
    public function geoIndex($columns, $name = null, $indexOptions = [])
    {
        return $this->spatialIndex($columns, $name, $indexOptions);
    }

    /**
     *  Specify a inverted index for the table.
     *
     * @param  array<mixed>|null|string  $columns
     * @param  null|string  $name
     * @param  array<mixed>  $indexOptions
     * @return Fluent
     */
    public function invertedIndex($columns = null, $name = null, $indexOptions = [])
    {
        return $this->indexCommand('inverted', $columns, $name, $indexOptions);
    }

    public function persistentIndex(array $columns = null, string $name = null, array $indexOptions = []): Fluent
    {
        return $this->indexCommand('persistent', $columns, $name, $indexOptions);
    }

    /**
     * @param array<string>|null|string $columns
     * @param string|null $name
     * @param array<mixed> $indexOptions
     * @return Fluent
     */
    public function primary($columns = null, string $name = null, array $indexOptions = []): Fluent
    {
        $indexOptions['unique'] = true;

        return $this->indexCommand('persistent', $columns, $name, $indexOptions);
    }

    /**
     * Create a TTL index for the table.
     *
     * @param string $columns
     * @param int $expireAfter
     * @param null $name
     * @param array $indexOptions
     * @return Fluent
     */
    public function ttlIndex($columns, $expireAfter, $name = null, $indexOptions = []): Fluent
    {
        $indexOptions['expireAfter'] = $expireAfter;
        return $this->indexCommand('ttl', $columns, $name, $indexOptions);
    }

    /**
     * Specify a unique index for the table.
     *
     * @param  string|array  $columns
     * @param  string  $name
     * @param  string|null  $algorithm
     */
    public function unique($columns = null, $name = null, $algorithm = null): Fluent
    {
        $type = $this->mapIndexAlgorithm($algorithm);

        $indexOptions = [];
        $indexOptions['unique'] = true;

        return $this->indexCommand($type, $columns, $name, $indexOptions);
    }

    /**
     * @throws ArangoException
     */
    public function executeIndexCommand(Fluent $command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery('/* ' . $command->explanation . " */\n", []);

            return;
        }

        // Skip persistent indexes set solely on id - ArangoDB already sets a unique index on _key.
        if (
            $command->type === 'persistent'
            && count($command->columns) === 1
            && $command->columns[0] === '_key'
        ) {
            return;
        }

        $options = [
            'type' => $command->type,
            'fields' => $command->columns,
            'unique' => $command->unique,
            'options' => $command->indexOptions,
        ];

        if (isset($command->indexOptions) && is_array($command->indexOptions)) {
            $options = array_merge($options, $command->indexOptions);
        }

        $this->schemaManager->createIndex($this->table, $options);
    }

    /**
     * Indicate that the given index should be dropped.
     */
    public function dropIndex(string $name): Fluent
    {
        $parameters = [];
        $parameters['name'] = 'dropIndex';
        $parameters['index'] = $name;
        $parameters['explanation'] = "Drop the '" . $name . "' index on the {$this->table} table.";
        $parameters['handler'] = 'collection';

        return $this->addCommand('dropIndex', $parameters);
    }

    /**
     * Indicate that the given index should be dropped.
     */
    public function dropPersistentIndex(string $name): Fluent
    {
        return $this->dropIndex($name);
    }

    /**
     * Indicate that the given index should be dropped.
     */
    public function dropPrimary(string $name): Fluent
    {
        return $this->dropIndex($name);
    }

    /**
     * Indicate that the given index should be dropped.
     */
    public function dropUnique(string $name): Fluent
    {
        return $this->dropIndex($name);
    }

    /**
     * Indicate that the given index should be dropped.
     */
    public function dropSpatialIndex(string $name): Fluent
    {
        return $this->dropIndex($name);
    }

    /**
     * Indicate that the given index should be dropped.
     */
    public function dropInvertedIndex(string $name): Fluent
    {
        return $this->dropIndex($name);
    }



    /**
     * Drop the index by first getting all the indexes on the table; then selecting the matching one
     * by name.
     */
    public function executeDropIndexCommand(Fluent $command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery('/* ' . $command->explanation . " */\n", []); // @phpstan-ignore-line

            return;
        }
        $indexes = $this->schemaManager->getIndexes($this->table);
        $arrayIndex = array_search($command->index, array_column($indexes, 'name'), true);
        $indexId = $indexes[$arrayIndex]->id;

        $this->schemaManager->deleteIndex($indexId);
    }

    /**
     * @param  string|null  $algorithm
     * @return mixed|string
     */
    protected function mapIndexAlgorithm($algorithm): mixed
    {
        $algorithm = strtoupper($algorithm);

        $algorithmConversion = [
            'HASH' => 'hash',
            'BTREE' => 'persistent',
            'RTREE' => 'geo',
            'TTL' => 'ttl',
        ];

        return (isset($algorithmConversion[$algorithm])) ? $algorithmConversion[$algorithm] : 'persistent';
    }

    /**
     * Create a default index name for the table.
     *
     * @param  string  $type
     */
    public function createIndexName($type, array $columns, array $options = []): string
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
