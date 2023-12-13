<?php

use ArangoClient\Exceptions\ArangoException;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

beforeEach(function () {
    $this->databaseMigrationRepository = new DatabaseMigrationRepository(app('db'), 'migrations');

    $this->schemaManager = $this->connection->getArangoClient()->schema();
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

    $results = $this->databaseMigrationRepository->getRan();
    $this->assertNotEmpty($results);

    $migration = (object) ['migration' => $filename];
    $this->databaseMigrationRepository->delete($migration);

    $results = $this->databaseMigrationRepository->getRan();
    expect($results)->toBeEmpty();
});

test('deleteRepository')->todo();

test('getLast', function () {
    //FIXME: original function uses subquery where

    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getLastMigration1', 60000);
    $this->databaseMigrationRepository->log('getLastMigration2', 60001);
    $this->databaseMigrationRepository->log('getLastMigration3', 60001);

    $lastBatch = $this->databaseMigrationRepository->getLast();

    expect(count($lastBatch))->toEqual(2);
    expect(current($lastBatch)->batch)->toEqual(60001);

    $this->databaseMigrationRepository->delete('getLastMigration1');
    $this->databaseMigrationRepository->delete('getLastMigration2');
    $this->databaseMigrationRepository->delete('getLastMigration3');
})->todo();

test('getLastBatchNumber', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getLastBatchNumberTest', 666);
    $this->databaseMigrationRepository->log('getLastBatchNumberTest', 667);

    $result = $this->databaseMigrationRepository->getLastBatchNumber();

    expect($result)->toBeNumeric();
    expect($result)->toEqual(667);

    $this->databaseMigrationRepository->delete('getLastBatchNumberTest');
    $this->databaseMigrationRepository->delete('getLastBatchNumberTest');
});

test('getMigrationBatches', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getMigrationBatches1', 32);
    $this->databaseMigrationRepository->log('getMigrationBatches2', 33);
    $this->databaseMigrationRepository->log('getMigrationBatches3', 32);

    $batches = $this->databaseMigrationRepository->getMigrationBatches();

    expect($batches)->toBeArray();
    expect(count($batches))->toEqual(3);

    foreach($batches as $key => $value) {
        $migration = (object)['migration' => $key];
        $this->databaseMigrationRepository->delete($migration);
    }
});

test('getMigrations', function () {
    // FIXME: getMigrations doesn't return any results,
    // while the performed query DOES return results when executed within the DB

    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getMigrationBatches1', 42);
    $this->databaseMigrationRepository->log('getMigrationBatches2', 42);
    $this->databaseMigrationRepository->log('getMigrationBatches3', 43);

    $migrations = $this->databaseMigrationRepository->getMigrations(1);

    ray($migrations);

    expect($migrations)->toBeArray();
    expect(count($migrations))->toEqual(2);

    $this->databaseMigrationRepository->delete('getMigrationBatches1');
    $this->databaseMigrationRepository->delete('getMigrationBatches2');
    $this->databaseMigrationRepository->delete('getMigrationBatches3');
})->todo();

test('getNextBatchNumber')->todo();

test('getRan', function () {
    $this->connection->getArangoClient()->schema()->truncateCollection('migrations');

    $this->databaseMigrationRepository->log('getRanMigration1', 50);
    $this->databaseMigrationRepository->log('getRanMigration2', 53);
    $this->databaseMigrationRepository->log('getRanMigration3', 67);

    $result = $this->databaseMigrationRepository->getRan();

    expect(count($result))->toEqual(3);
    expect($result[0])->toEqual('getRanMigration1');

    $this->databaseMigrationRepository->delete('getRanMigration1');
    $this->databaseMigrationRepository->delete('getRanMigration2');
    $this->databaseMigrationRepository->delete('getRanMigration3');
});

test('log', function () {
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

test('repositoryExists')->todo();
