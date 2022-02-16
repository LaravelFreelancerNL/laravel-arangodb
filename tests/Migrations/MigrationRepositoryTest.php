<?php

use ArangoClient\Exceptions\ArangoException;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;
use Tests\TestCase;


beforeEach(function () {
    $this->databaseMigrationRepository = new DatabaseMigrationRepository(app()['db'], $this->collection);

    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

test('migrations collection is created', function () {
    if ($this->databaseMigrationRepository->repositoryExists()) {
        $this->expectException(ArangoException::class);
        $this->withoutExceptionHandling();
    }

    $this->databaseMigrationRepository->createRepository();

    expect($this->databaseMigrationRepository->repositoryExists())->toBeTrue();
    expect($this->schemaManager->hasCollection($this->collection))->toBeTrue();
});

test('log creates migration entry', function () {
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
    expect($results->batch)->toBe($batch);

    $this->databaseMigrationRepository->delete($filename);
});

test('get number of last batch', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

    $this->databaseMigrationRepository->log('getLastBatchNumberTest', 666);
    $this->databaseMigrationRepository->log('getLastBatchNumberTest', 667);

    $result = $this->databaseMigrationRepository->getLastBatchNumber();

    expect($result)->toBeNumeric();
    expect($result)->toEqual(667);

    $this->databaseMigrationRepository->delete('getLastBatchNumberTest');
    $this->databaseMigrationRepository->delete('getLastBatchNumberTest');
});

test('get all ran migrationfiles', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

    $this->databaseMigrationRepository->log('getRanMigration1', 50);
    $this->databaseMigrationRepository->log('getRanMigration2', 53);
    $this->databaseMigrationRepository->log('getRanMigration3', 67);

    $result = $this->databaseMigrationRepository->getRan();

    expect(count($result))->toEqual(3);

    $this->databaseMigrationRepository->delete('getRanMigration1');
    $this->databaseMigrationRepository->delete('getRanMigration2');
    $this->databaseMigrationRepository->delete('getRanMigration3');
});

test('delete migration', function () {
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
    expect($results)->toBeEmpty();
});

test('get last migration', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

    $this->databaseMigrationRepository->log('getLastMigration1', 60000);
    $this->databaseMigrationRepository->log('getLastMigration2', 60001);
    $this->databaseMigrationRepository->log('getLastMigration3', 60001);

    $lastBatch = $this->databaseMigrationRepository->getLast();

    expect(count($lastBatch))->toEqual(2);
    expect(current($lastBatch)->batch)->toEqual(60001);

    $this->databaseMigrationRepository->delete('getLastMigration1');
    $this->databaseMigrationRepository->delete('getLastMigration2');
    $this->databaseMigrationRepository->delete('getLastMigration3');
});

test('get migration batches', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

    $this->databaseMigrationRepository->log('getMigrationBatches1', 32);
    $this->databaseMigrationRepository->log('getMigrationBatches2', 33);
    $this->databaseMigrationRepository->log('getMigrationBatches3', 32);

    $batches = $this->databaseMigrationRepository->getMigrationBatches();

    expect($batches)->toBeArray();
    expect(count($batches))->toEqual(3);

    $this->databaseMigrationRepository->delete('getMigrationBatches1');
    $this->databaseMigrationRepository->delete('getMigrationBatches2');
    $this->databaseMigrationRepository->delete('getMigrationBatches3');
});

test('get migrations', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection($this->collection);

    $this->databaseMigrationRepository->log('getMigrationBatches1', 42);
    $this->databaseMigrationRepository->log('getMigrationBatches2', 43);
    $this->databaseMigrationRepository->log('getMigrationBatches3', 42);

    $migrations = $this->databaseMigrationRepository->getMigrations(2);

    expect($migrations)->toBeArray();
    expect(count($migrations))->toEqual(2);

    $this->databaseMigrationRepository->delete('getMigrationBatches1');
    $this->databaseMigrationRepository->delete('getMigrationBatches2');
    $this->databaseMigrationRepository->delete('getMigrationBatches3');
});
