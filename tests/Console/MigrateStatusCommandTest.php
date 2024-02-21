<?php

declare(strict_types=1);

use Illuminate\Database\QueryException as IlluminateQueryException;

test('migrate:status', function () {
    $this->artisan('migrate:status', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../Setup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/')
        ],
        '--realpath' => true,
    ])
        ->expectsOutputToContain('2024_01_04_145621_create_house_search_alias_view ......................................................................................... [1] Ran')
        ->assertExitCode(0);
});

test('migrate:status --database=arangodb', function () {
    $this->artisan('migrate:status', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../Setup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/')
        ],
        '--realpath' => true,
        '--database' => 'arangodb',
    ])
        ->expectsOutputToContain('2024_01_04_145621_create_house_search_alias_view ......................................................................................... [1] Ran')
        ->assertExitCode(0);
});

test('migrate:status --database=sqlite', function () {
    $this->artisan('migrate:status', [
        '--database' => 'sqlite',
    ])->run();
})->throws(IlluminateQueryException::class);
