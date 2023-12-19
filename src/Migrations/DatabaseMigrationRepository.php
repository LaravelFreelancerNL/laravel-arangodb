<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Migrations;

use Illuminate\Database\ConnectionResolverInterface as IlluminateResolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository as IlluminateDatabaseMigrationRepository;
use LaravelFreelancerNL\Aranguent\Query\Builder;

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

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schemaManager = $this->getConnection()->getArangoClient()->schema();

        $schemaManager->createCollection($this->table);
    }

    /**
     * Get the list of migrations.
     *
     * @param  int  $steps
     * @return array
     */
    public function getMigrations($steps)
    {
        //TODO: the only difference with the parent function is that type of the batch value:
        // 1 instead of '1'. This should probably be changed in the Laravel framework as it
        // seems unnecessary to use a numeric string here.

        $query = $this->table()
            ->where('batch', '>=', 1)
            ->orderBy('batch', 'desc')
            ->orderBy('migration', 'desc')
            ->take($steps);

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
        return $this->table();
    }
}
