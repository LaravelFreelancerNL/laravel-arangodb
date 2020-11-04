<?php

namespace LaravelFreelancerNL\Aranguent\Migrations;

use Illuminate\Database\ConnectionResolverInterface as IlluminateResolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository as IlluminateDatabaseMigrationRepository;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

class DatabaseMigrationRepository extends IlluminateDatabaseMigrationRepository
{
    /**
     * The name of the migration collection.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database migration repository instance.
     *
     * @param IlluminateResolver $resolver
     * @param table $
     */
    public function __construct(IlluminateResolver $resolver, $table)
    {
        $this->table = $table;

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
        $qb = (new QueryBuilder())->for('m', 'migrations')
            ->sort('m.batch', 'm.migrations')
            ->return('m.migration')
            ->get();

        return $this->getConnection()->select($qb->query);

//        return $this->table()
//                ->orderBy('batch', 'asc')
//                ->orderBy('migration', 'asc')
//                ->pluck('migration')->all();
    }

    /**
     * Get list of migrations.
     *
     * @param int $steps
     *
     * @return array
     */
    public function getMigrations($steps)
    {
        $bindings['steps'] = $steps;

        $qb = (new QueryBuilder())->for('m', 'migrations')
            ->filter('m.batch', '>=', 1)
            ->sort([['m.batch', 'DESC'], ['m.migration', 'DESC']])
            ->limit($steps)
            ->return(['migration' => 'm.migration', 'batch' => 'm.batch'])
            ->get();

        return $this->getConnection()->select($qb->query);
    }

    public function getLast()
    {
        $batch = $this->getLastBatchNumber();

        $qb = (new QueryBuilder())->for('m', 'migrations')
            ->filter('m.batch', '==', $batch)
            ->sort('m.migration', 'desc')
            ->return('m')
            ->get();

        return $this->getConnection()->select($qb->query);
    }

    /**
     * Get the completed migrations with their batch numbers.
     *
     * @return array
     */
    public function getMigrationBatches()
    {
        $qb = (new QueryBuilder())->for('m', 'migrations')
            ->sort([['m.batch'], ['m.migration']])
            ->return(['batch' => 'm.batch', 'migration' => 'm.migration'])
            ->get();

        return $this->getConnection()->select($qb->query);

//        return $this->table()
//                ->orderBy('batch', 'asc')
//                ->orderBy('migration', 'asc')
//                ->pluck('batch', 'migration')->all();
    }

    /**
     * Log that a migration was run.
     *
     * @param string $file
     * @param int    $batch
     */
    public function log($file, $batch)
    {
        $qb = (new QueryBuilder())->insert(['migration' => $file, 'batch' => $batch], 'migrations')->get();

        $this->getConnection()->insert($qb->query, $qb->binds);

//        $record = ['migration' => $file, 'batch' => $batch];
//
//        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log.
     *
     * @param object $migration
     *
     * @return void
     */
    public function delete($migration)
    {
        $qb = (new QueryBuilder())->for('m', 'migrations')
                ->filter('m.migration', '==', $migration->migration)
                ->remove('m', 'migrations')
                ->get();

        $this->getConnection()->delete($qb->query, $qb->binds);

//        $this->table()->where('migration', $migration->migration)->delete();
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
        $qb = new QueryBuilder();
        $qb = $qb->for('m', 'migrations')
            ->collect()
            ->aggregate('maxBatch', $qb->max('m.batch'))
            ->return('maxBatch')
            ->get();

        $results = current($this->getConnection()->select($qb->query));
        if ($results === null) {
            $results = 0;
        }

        return $results;
//        return $this->table()->max('batch');
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $collectionHandler = $this->getConnection()->getCollectionHandler();

        $collectionHandler->create($this->table);

//        $schema = $this->getConnection()->getSchemaBuilder();
//
//        $schema->create($this->table, function ($table) {
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

        return $collectionHandler->has($this->table);

//        $schema = $this->getConnection()->getSchemaBuilder();
//
//        return $schema->hasCollection($this->table);
    }

    /**
     * Get a query builder for the migration collection.
     *
     * @return Builder
     */
    protected function collection()
    {
        return $this->getConnection()->collection($this->table);
    }

    /**
     * {@inheritdoc}
     *
     * @return Builder
     */
    protected function table()
    {
        return $this->collection();
    }
}
