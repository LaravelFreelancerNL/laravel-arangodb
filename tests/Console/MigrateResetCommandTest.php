<?php

use Illuminate\Database\QueryException as IlluminateQueryException;

beforeEach(function () {
    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

test('migrate:reset', function () {
    $path = [
        realpath(__DIR__ . '/../Setup/Database/Migrations')
    ];

    $this->artisan('migrate:reset', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../Setup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/')
        ],
        '--realpath' => true,
    ])->assertExitCode(0);

    $collections = $this->schemaManager->getCollections(true);
    expect(count($collections))->toBe(1);

    refreshDatabase();
});

test('migrate:reset --database=arangodb', function () {
    $path = [
        realpath(__DIR__ . '/../Setup/Database/Migrations')
    ];

    $this->artisan('migrate:reset', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../Setup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/')
        ],
        '--realpath' => true,
    ])->assertExitCode(0);

    $collections = $this->schemaManager->getCollections(true);
    expect(count($collections))->toBe(1);

    refreshDatabase();
});

test('migrate:reset --database=sqlite', function () {
    $this->artisan('migrate:reset', [
        '--database' => 'sqlite',
    ])->run();
})->throws(IlluminateQueryException::class);
