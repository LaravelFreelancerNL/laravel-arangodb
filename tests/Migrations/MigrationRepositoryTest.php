<?php

use ArangoClient\Exceptions\ArangoException;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;

beforeEach(function () {
    $this->databaseMigrationRepository = new DatabaseMigrationRepository(app('db'), 'migrations');

    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

afterEach(function () {
    $migrations = $this->databaseMigrationRepository->getMigrations(10);
    foreach($migrations as $migration) {
        $this->databaseMigrationRepository->delete($migration);
    }
});

test('createRepository', function () {
    if ($this->databaseMigrationRepository->repositoryExists()) {
        $this->expectException(ArangoException::class);
        $this->withoutExceptionHandling();
    }

    $this->databaseMigrationRepository->createRepository();

    expect($this->databaseMigrationRepository->repositoryExists())->toBeTrue();
    expect($this->schemaManager->hasCollection($this->collection))->toBeTrue();
});

test('delete', function () {
    $filename = 'deletetest.php';
    $batch = 39;

    $this->databaseMigrationRepository->log($filename, $batch);

    $results = $this->databaseMigrationRepository->getMigrations(1);

    expect($results[0]->migration)->toBe('deletetest.php');

    $migration = (object) ['migration' => $filename];
    $this->databaseMigrationRepository->delete($migration);

    $results = $this->databaseMigrationRepository->getRan();

    expect($results)->toBeEmpty();
});

test('deleteRepository', function () {
    $this->databaseMigrationRepository->deleteRepository();

    $exists = $this->databaseMigrationRepository->repositoryExists();

    expect($exists)->toBeFalse();

    $result = $this->databaseMigrationRepository->createRepository();
});

test('getLast', function () {
    //FIXME: original function uses subquery where

    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getLastMigration1', 60000);
    $this->databaseMigrationRepository->log('getLastMigration2', 60001);
    $this->databaseMigrationRepository->log('getLastMigration3', 60001);

    $lastBatch = $this->databaseMigrationRepository->getLast();

    expect(count($lastBatch))->toEqual(2);
    expect(current($lastBatch)->batch)->toEqual(60001);
});

test('getLastBatchNumber', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getLastBatchNumberTest', 666);
    $this->databaseMigrationRepository->log('getLastBatchNumberTest', 667);

    $result = $this->databaseMigrationRepository->getLastBatchNumber();

    expect($result)->toBeNumeric();
    expect($result)->toEqual(667);
});

test('getMigrationBatches', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $batches = [
        "getMigrationBatches1" => 32,
        "getMigrationBatches3" => 32,
        "getMigrationBatches2" => 33
    ];

    foreach($batches as $migration => $batch) {
        $this->databaseMigrationRepository->log($migration, $batch);
    }

    $results = $this->databaseMigrationRepository->getMigrationBatches();

    expect($results)->toEqual($batches);
});

test('getMigrations', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getMigrationBatches1', 42);
    $this->databaseMigrationRepository->log('getMigrationBatches2', 42);
    $this->databaseMigrationRepository->log('getMigrationBatches3', 43);

    $migrations = $this->databaseMigrationRepository->getMigrations(2);

    expect($migrations)->toBeArray();
    expect(count($migrations))->toEqual(2);
});

test('getNextBatchNumber', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getLastBatchNumberTest', 666);

    $result = $this->databaseMigrationRepository->getNextBatchNumber();

    expect($result)->toEqual(667);
});

test('getRan', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getRanMigration1', 50);
    $this->databaseMigrationRepository->log('getRanMigration2', 53);
    $this->databaseMigrationRepository->log('getRanMigration3', 67);

    $result = $this->databaseMigrationRepository->getRan();

    expect(count($result))->toEqual(3);
    expect($result[0])->toEqual('getRanMigration1');
});

test('log', function () {
    $filename = 'logtest.php';
    $batch = 40;
    $this->databaseMigrationRepository->log($filename, $batch);

    $result = $this->databaseMigrationRepository->getMigrations(1);

    $this->assertNotEmpty($result);
    expect($result[0]->batch)->toBe($batch);
});

test('repositoryExists', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $result = $this->databaseMigrationRepository->repositoryExists();

    expect($result)->toBeTrue();
});
