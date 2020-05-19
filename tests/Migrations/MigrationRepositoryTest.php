<?php

namespace Tests\Migrations;

use ArangoDBClient\Document;
use ArangoDBClient\ServerException;
use Tests\TestCase;

class MigrationRepositoryTest extends TestCase
{
    /**
     * migrations collection is created.
     * @test
     */
    public function migrations_collection_is_created()
    {
        if ($this->databaseMigrationRepository->repositoryExists()) {
            $this->expectException(ServerException::class);
            $this->withoutExceptionHandling();
        }

        $this->databaseMigrationRepository->createRepository();

        $this->assertTrue($this->databaseMigrationRepository->repositoryExists());
        $this->assertTrue($this->collectionHandler->has($this->collection));
    }

    /**
     * log_creates_migration_entry.
     * @test
     */
    public function log_creates_migration_entry()
    {
        $filename = 'logtest.php';
        $batch = 40;
        $this->databaseMigrationRepository->log($filename, $batch);

        $this->collectionHandler->setDocumentClass(Document::class);
        $cursor = $this->collectionHandler->byExample('migrations', ['migration' => $filename, 'batch' => $batch]);
        $documents = collect($cursor->getAll());
        $this->assertInstanceOf(Document::class, $documents->first());
    }

    /**
     * get the highest batch value.
     * @test
     */
    public function get_the_last_batch_number()
    {
        $this->databaseMigrationRepository->log('getLastBatchNumberTest', 2);
        $this->databaseMigrationRepository->log('getLastBatchNumberTest', 2);

        $result = $this->databaseMigrationRepository->getLastBatchNumber();

        $this->assertIsNumeric($result);
        $this->assertEquals(2, $result);
    }

    /**
     * get all ran migration files.
     * @test
     */
    public function get_all_ran_migration_files()
    {
        $this->databaseMigrationRepository->log('getRanMigration1', 50);
        $this->databaseMigrationRepository->log('getRanMigration2', 53);
        $this->databaseMigrationRepository->log('getRanMigration3', 67);

        $result = $this->databaseMigrationRepository->getRan();

        $this->assertEquals(7, count($result));
    }

    /**
     * Delete migration.
     * @test
     */
    public function delete_migration()
    {
        $filename = 'deletetest.php';
        $batch = 39;

        $this->databaseMigrationRepository->log($filename, $batch);

        $this->collectionHandler->setDocumentClass(Document::class);
        $cursor = $this->collectionHandler->byExample('migrations', ['migration' => $filename, 'batch' => $batch]);
        $documents = collect($cursor->getAll());
        $this->assertInstanceOf(Document::class, $documents->first());

        $migration = (object) ['migration' => $filename];
        $this->databaseMigrationRepository->delete($migration);

        $this->collectionHandler->setDocumentClass(Document::class);
        $cursor = $this->collectionHandler->byExample('migrations', ['migration' => $filename, 'batch' => $batch]);
        $documents = collect($cursor->getAll());
        $this->assertNull($documents->first());
    }

    /**
     * get all migrations within the last batch.
     * @test
     */
    public function get_last_migration()
    {
        $this->databaseMigrationRepository->log('getLastMigration1', 60000);
        $this->databaseMigrationRepository->log('getLastMigration2', 60001);
        $this->databaseMigrationRepository->log('getLastMigration3', 60001);

        $lastBatch = $this->databaseMigrationRepository->getLast();

        $this->assertEquals(2, count($lastBatch));
        $this->assertEquals(60001, current($lastBatch)->batch);
    }

    /**
     * get migration batches.
     * @test
     */
    public function get_migration_batches()
    {
        $this->databaseMigrationRepository->log('getMigrationBatches1', 32);
        $this->databaseMigrationRepository->log('getMigrationBatches2', 33);
        $this->databaseMigrationRepository->log('getMigrationBatches3', 32);

        $batches = $this->databaseMigrationRepository->getMigrationBatches();

        $this->assertIsArray($batches);
        $this->assertEquals(7, count($batches));
    }

    /**
     * get migration batches.
     * @test
     */
    public function get_migrations()
    {
        $this->databaseMigrationRepository->log('getMigrationBatches1', 42);
        $this->databaseMigrationRepository->log('getMigrationBatches2', 43);
        $this->databaseMigrationRepository->log('getMigrationBatches3', 42);

        $migrations = $this->databaseMigrationRepository->getMigrations(2);

        $this->assertIsArray($migrations);
        $this->assertEquals(2, count($migrations));
    }
}
