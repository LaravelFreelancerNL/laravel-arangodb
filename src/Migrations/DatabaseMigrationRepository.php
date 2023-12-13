<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Migrations;

use Illuminate\Database\ConnectionResolverInterface as IlluminateResolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository as IlluminateDatabaseMigrationRepository;
use LaravelFreelancerNL\Aranguent\Connection;
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
     */
    public function __construct(IlluminateResolver $resolver, string $table)
    {
        parent::__construct($resolver, $table);
    }

//    /**
//     * Get the next migration batch number.
//     *
//     * @return int
//     */
//    public function getNextBatchNumber()
//    {
//        return $this->getLastBatchNumber() + 1;
//    }
//
//    /**
//     * Get the last migration batch number.
//     *
//     * @return int
//     */
//    public function getLastBatchNumber()
//    {
//        $qb = new QueryBuilder();
//        $qb = $qb->for('m', 'migrations')
//            ->collect()
//            ->aggregate('maxBatch', $qb->max('m.batch'))
//            ->return('maxBatch')
//            ->get();
//
//        $results = current($this->getConnection()->select($qb->query));
//        if ($results === null) {
//            $results = 0;
//        }
//
//        return $results;
//        //        return $this->table()->max('batch');
//    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schemaManager = $this->getConnection()->getArangoClient()->schema();

        $schemaManager->createCollection($this->table);

        //        $schema = $this->getConnection()->getSchemaBuilder();
        //
        //        $schema->create($this->table, function ($collection) {
        //            // The migrations collection is responsible for keeping track of which of the
        //            // migrations have actually run for the application. We'll create the
        //            // collection to hold the migration file's path as well as the batch ID.
        //            $collection->increments('id');
        //            $collection->string('migration');
        //            $collection->integer('batch');
        //        });
    }

    /**
     * Get the list of migrations.
     *
     * @param  int  $steps
     * @return array
     */
    public function getMigrations($steps)
    {
        $query = $this->table()
            ->where('batch', '>=', '1')
            ->orderBy('batch', 'desc')
            ->orderBy('migration', 'desc')
            ->take($steps);

        ray('getMigrations X', $query, $query->toSql(),$query->bindings, $query->get());

        return $query->get()->all();
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schemaManager = $this->getConnection()->getArangoClient()->schema();

        return $schemaManager->hasCollection($this->table);

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
        return $this->getConnection()->table($this->table);
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
