<?php

namespace Tests\Migrations;

use ArangoDBClient\Document;
use ArangoDBClient\ServerException;
use Tests\TestCase;

class MigrationRepositoryTest extends TestCase
{
    public function testMigrationsCollectionIsCreated()
    {
        if ($this->databaseMigrationRepository->repositoryExists()) {
            $this->expectException(ServerException::class);
            $this->withoutExceptionHandling();
        }

        $this->databaseMigrationRepository->createRepository();

        $this->assertTrue($this->databaseMigrationRepository->repositoryExists());
        $this->assertTrue($this->collectionHandler->has($this->collection));
    }

    public function testLogCreatesMigrationEntry()
    {
        $filename = 'logtest.php';
        $batch = 40;
        $this->databaseMigrationRepository->log($filename, $batch);

        $this->collectionHandler->setDocumentClass(Document::class);
        $cursor = $this->collectionHandler->byExample('migrations', ['migration' => $filename, 'batch' => $batch]);
        $documents = collect($cursor->getAll());
        $this->assertInstanceOf(Document::class, $documents->first());
    }

    public function testGetNumberOfLastBatch()
    {
        $this->databaseMigrationRepository->log('getLastBatchNumberTest', 2);
        $this->databaseMigrationRepository->log('getLastBatchNumberTest', 2);

        $result = $this->databaseMigrationRepository->getLastBatchNumber();

        $this->assertIsNumeric($result);
        $this->assertEquals(2, $result);
    }

    public function testGetAllRanMigrationfiles()
    {
        $this->databaseMigrationRepository->log('getRanMigration1', 50);
        $this->databaseMigrationRepository->log('getRanMigration2', 53);
        $this->databaseMigrationRepository->log('getRanMigration3', 67);

        $result = $this->databaseMigrationRepository->getRan();

        $this->assertEquals(9, count($result));
    }

    public function testDeleteMigration()
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

    public function testGetLastMigration()
    {
        $this->databaseMigrationRepository->log('getLastMigration1', 60000);
        $this->databaseMigrationRepository->log('getLastMigration2', 60001);
        $this->databaseMigrationRepository->log('getLastMigration3', 60001);

        $lastBatch = $this->databaseMigrationRepository->getLast();

        $this->assertEquals(2, count($lastBatch));
        $this->assertEquals(60001, current($lastBatch)->batch);
    }

    public function testGetMigrationBatches()
    {
        $this->databaseMigrationRepository->log('getMigrationBatches1', 32);
        $this->databaseMigrationRepository->log('getMigrationBatches2', 33);
        $this->databaseMigrationRepository->log('getMigrationBatches3', 32);

        $batches = $this->databaseMigrationRepository->getMigrationBatches();

        $this->assertIsArray($batches);
        $this->assertEquals(9, count($batches));
    }

    public function testGetMigrations()
    {
        $this->databaseMigrationRepository->log('getMigrationBatches1', 42);
        $this->databaseMigrationRepository->log('getMigrationBatches2', 43);
        $this->databaseMigrationRepository->log('getMigrationBatches3', 42);

        $migrations = $this->databaseMigrationRepository->getMigrations(2);

        $this->assertIsArray($migrations);
        $this->assertEquals(2, count($migrations));
    }
}
