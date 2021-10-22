<?php

namespace Tests\Migrations;

use ArangoClient\Exceptions\ArangoException;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;
use Tests\TestCase;

class MigrationRepositoryTest extends TestCase
{
    protected $collection = 'migrations';

    protected ?DatabaseMigrationRepository $databaseMigrationRepository = null;

    protected \ArangoClient\Schema\SchemaManager $schemaManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseMigrationRepository = new DatabaseMigrationRepository($this->app['db'], $this->collection);

        $this->schemaManager = $this->connection->getArangoClient()->schema();
    }

    public function testMigrationsCollectionIsCreated()
    {
        if ($this->databaseMigrationRepository->repositoryExists()) {
            $this->expectException(ArangoException::class);
            $this->withoutExceptionHandling();
        }

        $this->databaseMigrationRepository->createRepository();

        $this->assertTrue($this->databaseMigrationRepository->repositoryExists());
        $this->assertTrue($this->schemaManager->hasCollection($this->collection));
    }

    public function testLogCreatesMigrationEntry()
    {
        $filename = 'logtest.php';
        $batch = 40;
        $this->databaseMigrationRepository->log($filename, $batch);

        $qb = (new QueryBuilder())->for('m', 'migrations')
            ->filter('m.migration', '==', 'logtest.php')
            ->filter('m.batch', '==', 40)
            ->return('m')
            ->get();
        $results = current($this->connection->select($qb->query));

        $this->assertNotEmpty($results);
        $this->assertSame($batch, $results->batch);

        $this->databaseMigrationRepository->delete($filename);
    }

    public function testGetNumberOfLastBatch()
    {
        $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

        $this->databaseMigrationRepository->log('getLastBatchNumberTest', 666);
        $this->databaseMigrationRepository->log('getLastBatchNumberTest', 667);

        $result = $this->databaseMigrationRepository->getLastBatchNumber();

        $this->assertIsNumeric($result);
        $this->assertEquals(667, $result);

        $this->databaseMigrationRepository->delete('getLastBatchNumberTest');
        $this->databaseMigrationRepository->delete('getLastBatchNumberTest');
    }

    public function testGetAllRanMigrationfiles()
    {
        $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

        $this->databaseMigrationRepository->log('getRanMigration1', 50);
        $this->databaseMigrationRepository->log('getRanMigration2', 53);
        $this->databaseMigrationRepository->log('getRanMigration3', 67);

        $result = $this->databaseMigrationRepository->getRan();

        $this->assertEquals(3, count($result));

        $this->databaseMigrationRepository->delete('getRanMigration1');
        $this->databaseMigrationRepository->delete('getRanMigration2');
        $this->databaseMigrationRepository->delete('getRanMigration3');
    }

    public function testDeleteMigration()
    {
        $filename = 'deletetest.php';
        $batch = 39;

        $this->databaseMigrationRepository->log($filename, $batch);

        $qb = (new QueryBuilder())->for('m', 'migrations')
            ->filter('m.migration', '==', $filename)
            ->filter('m.batch', '==', $batch)
            ->return('m')
            ->get();
        $results = current($this->connection->select($qb->query));
        $this->assertNotEmpty($results);

        $migration = (object) ['migration' => $filename];
        $this->databaseMigrationRepository->delete($migration);

        $qb = (new QueryBuilder())->for('m', 'migrations')
            ->filter('m.migration', '==', $filename)
            ->filter('m.batch', '==', $batch)
            ->return('m')
            ->get();
        $results = current($this->connection->select($qb->query));
        $this->assertEmpty($results);
    }

    public function testGetLastMigration()
    {
        $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

        $this->databaseMigrationRepository->log('getLastMigration1', 60000);
        $this->databaseMigrationRepository->log('getLastMigration2', 60001);
        $this->databaseMigrationRepository->log('getLastMigration3', 60001);

        $lastBatch = $this->databaseMigrationRepository->getLast();

        $this->assertEquals(2, count($lastBatch));
        $this->assertEquals(60001, current($lastBatch)->batch);

        $this->databaseMigrationRepository->delete('getLastMigration1');
        $this->databaseMigrationRepository->delete('getLastMigration2');
        $this->databaseMigrationRepository->delete('getLastMigration3');
    }

    public function testGetMigrationBatches()
    {
        $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

        $this->databaseMigrationRepository->log('getMigrationBatches1', 32);
        $this->databaseMigrationRepository->log('getMigrationBatches2', 33);
        $this->databaseMigrationRepository->log('getMigrationBatches3', 32);

        $batches = $this->databaseMigrationRepository->getMigrationBatches();

        $this->assertIsArray($batches);
        $this->assertEquals(3, count($batches));

        $this->databaseMigrationRepository->delete('getMigrationBatches1');
        $this->databaseMigrationRepository->delete('getMigrationBatches2');
        $this->databaseMigrationRepository->delete('getMigrationBatches3');
    }

    public function testGetMigrations()
    {
        $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

        $this->databaseMigrationRepository->log('getMigrationBatches1', 42);
        $this->databaseMigrationRepository->log('getMigrationBatches2', 43);
        $this->databaseMigrationRepository->log('getMigrationBatches3', 42);

        $migrations = $this->databaseMigrationRepository->getMigrations(2);

        $this->assertIsArray($migrations);
        $this->assertEquals(2, count($migrations));

        $this->databaseMigrationRepository->delete('getMigrationBatches1');
        $this->databaseMigrationRepository->delete('getMigrationBatches2');
        $this->databaseMigrationRepository->delete('getMigrationBatches3');
    }
}
