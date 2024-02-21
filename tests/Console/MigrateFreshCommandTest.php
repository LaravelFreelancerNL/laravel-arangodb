<?php

use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\QueryException as IlluminateQueryException;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->databaseMigrationRepository = new DatabaseMigrationRepository(app('db'), 'migrations');

    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

test('migrate:fresh', function () {
    $path = [
        realpath(__DIR__ . '/../Setup/Database/Migrations')
    ];

    $this->artisan('migrate:fresh', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../Setup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/')
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,

    ])->assertExitCode(0);

    $collections = $this->schemaManager->getCollections(true);
    expect(count($collections))->toBe(10);
});

test('migrate:fresh --database=arangodb', function () {
    $path = [
        realpath(__DIR__ . '/../Setup/Database/Migrations')
    ];

    $this->artisan('migrate:fresh', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../Setup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/')
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,

    ])->assertExitCode(0);

    $collections = $this->schemaManager->getCollections(true);
    expect(count($collections))->toBe(10);
});

test('migrate:fresh --database=sqlite', function () {
    $this->artisan('migrate:fresh', [
        '--database' => 'sqlite',
    ])->assertExitCode(0);
})->throws(IlluminateQueryException::class);
