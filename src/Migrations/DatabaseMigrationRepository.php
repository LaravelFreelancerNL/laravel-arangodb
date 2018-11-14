<?php

namespace LaravelFreelancerNL\Aranguent\Migrations;

use Illuminate\Database\ConnectionResolverInterface as IlluminateResolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository as IlluminateDatabaseMigrationRepository;

class DatabaseMigrationRepository extends IlluminateDatabaseMigrationRepository
{
    // TODO: Revert the direct use of the collectionHandler to the Schema & Query builder after those is finished.

    /**
     * The name of the migration collection.
     *
     * @var string
     */
    protected $collection;

    /**
     * Create a new database migration repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $collection
     * @return void
     */
    public function __construct(IlluminateResolver $resolver, $collection)
    {
        $this->collection = $collection;

        $this->resolver = $resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Get the completed migrations.
     *
     * @return array
     */
    public function getRan()
    {
        $query = '
            FOR m IN migrations
                SORT m.batch, m.migration
                RETURN m.migration
        ';

        return $this->getConnection()->select($query, []);

//        return $this->collection()
//                ->orderBy('batch', 'asc')
//                ->orderBy('migration', 'asc')
//                ->pluck('migration')->all();
    }

    /**
     * Get list of migrations.
     *
     * @param  int  $steps
     * @return array
     */
    public function getMigrations($steps)
    {
        $query = "
            FOR m IN migrations
                FILTER m.batch >= 1
                SORT m.batch DESC, m.migration DESC
                LIMIT @steps
                RETURN { 'migration' : m.migration, 'batch' : m.batch }
        ";
        $bindings['steps'] = $steps;

        return $this->getConnection()->select($query, $bindings);

//        $query = $this->collection()->where('batch', '>=', '1');
//
//        return $query->orderBy('batch', 'desc')
//                     ->orderBy('migration', 'desc')
//                     ->take($steps)->get()->all();
    }

    public function getLast()
    {
        $batch = $this->getLastBatchNumber();
        $query = '
            FOR m IN migrations
                FILTER m.batch == @batch
                SORT m.migration DESC
                RETURN m
        ';
        $bindings['batch'] = $batch;

        return $this->getConnection()->select($query, $bindings);

//        $query = $this->collection()->where('batch', );
//
//        return $query->orderBy('migration', 'desc')->get()->all();
    }

    /**
     * Get the completed migrations with their batch numbers.
     *
     * @return array
     */
    public function getMigrationBatches()
    {
        $query = "
            FOR m IN migrations
                SORT m.batch, m.migration
                RETURN { 'batch' : m.batch, 'migration' : m.migration }
        ";

        return $this->getConnection()->select($query, []);

//        return $this->collection()
//                ->orderBy('batch', 'asc')
//                ->orderBy('migration', 'asc')
//                ->pluck('batch', 'migration')->all();
    }

    /**
     * Log that a migration was run.
     *
     * @param string $file
     * @param int $batch
     */
    public function log($file, $batch)
    {
        $query = '
            INSERT { migration: @file, batch: @batch } 
              INTO migrations
        ';
        $bindings['file'] = $file;
        $bindings['batch'] = $batch;
        $this->getConnection()->insert($query, $bindings);

//        $record = ['migration' => $file, 'batch' => $batch];
//
//        $this->collection()->insert($record);
    }

    /**
     * Remove a migration from the log.
     *
     * @param  object  $migration
     * @return void
     */
    public function delete($migration)
    {
        $query = '
            FOR m IN migrations
                FILTER m.migration == @migration
                REMOVE m IN migrations
        ';
        $bindings['migration'] = $migration->migration;

        $this->getConnection()->delete($query, $bindings);

        //        $this->collection()->where('migration', $migration->migration)->delete();
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        $query = '
            FOR m IN migrations
                COLLECT AGGREGATE 
                    maxBatch = MAX(m.batch)
                    RETURN maxBatch
        ';
        $results = current($this->getConnection()->select($query, []));
        if ($results === null) {
            $results = 0;
        }

        return $results;
//        return $this->collection()->max('batch');
    }

    /**
     * Create the migration repository data store.
     * @return void
     */
    public function createRepository()
    {
        $collectionHandler = $this->getConnection()->getCollectionHandler();

        $collectionHandler->create($this->collection);

//        $schema = $this->getConnection()->getSchemaBuilder();
//
//        $schema->create($this->collection, function ($collection) {
//            // The migrations collection is responsible for keeping track of which of the
//            // migrations have actually run for the application. We'll create the
//            // collection to hold the migration file's path as well as the batch ID.
//            $collection->increments('id');
//            $collection->string('migration');
//            $collection->integer('batch');
//        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $collectionHandler = $this->getConnection()->getCollectionHandler();

        return $collectionHandler->has($this->collection);

//        $schema = $this->getConnection()->getSchemaBuilder();
//
//        return $schema->hasCollection($this->collection);
    }

    /**
     * Get a query builder for the migration collection.
     *
     * @return \LaravelFreelancerNL\Aranguent\Query\Builder
     */
    protected function collection()
    {
        return $this->getConnection()->collection($this->collection);
    }

    /**
     * {@inheritdoc}
     * @return \LaravelFreelancerNL\Aranguent\Query\Builder
     */
    protected function table()
    {
        return $this->collection();
    }
}
