<?php

declare(strict_types=1);

use Illuminate\Database\QueryException as IlluminateQueryException;

beforeEach(function () {
    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

test('migrate:install', function () {
    $this->clearDatabase();

    $this->artisan('migrate:install')->assertExitCode(0);

    $collections = $this->schemaManager->getCollections(true);

    expect(count($collections))->toBe(1);
    expect($collections[0]->name)->toBe('migrations');

    refreshDatabase();
});

test('migrate:install --database=arangodb', function () {
    $this->clearDatabase();

    $this->artisan('migrate:install', ['--database' => 'arangodb'])->assertExitCode(0);

    $collections = $this->schemaManager->getCollections(true);

    expect(count($collections))->toBe(1);
    expect($collections[0]->name)->toBe('migrations');

    refreshDatabase();
});

test('migrate:install --database=sqlite', function () {
    $this->artisan('migrate:install', ['--database' => 'sqlite'])->assertExitCode(0);
})->throws(IlluminateQueryException::class);
