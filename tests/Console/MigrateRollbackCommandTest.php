<?php

use Illuminate\Database\QueryException as IlluminateQueryException;

beforeEach(function () {
    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

test('migrate:rollback', function () {
    $path = [
        realpath(__DIR__ . '/../Setup/Database/Migrations')
    ];

    $this->artisan('migrate:rollback', [
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

test('migrate:rollback --database=arangodb', function () {
    $path = [
        realpath(__DIR__ . '/../Setup/Database/Migrations')
    ];

    $this->artisan('migrate:rollback', [
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

test('migrate:rollback --database=sqlite', function () {
    $this->artisan('migrate:rollback', [
        '--database' => 'sqlite',
    ])->assertExitCode(0);
})->throws(IlluminateQueryException::class);
