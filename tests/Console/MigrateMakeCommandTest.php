<?php

$migrationPath = __DIR__ . '/../../vendor/orchestra/testbench-core/laravel/database/migrations';

test('make:migration', function () use ($migrationPath) {
    $this->artisan('make:migration')
        ->expectsQuestion('What should the migration be named?', 'create_tests_table')
        ->assertExitCode(0);

    $migrationFiles = glob($migrationPath.'/*'.'_create_tests_table.php');

    $contents = file_get_contents($migrationFiles[0]);

    expect(str_contains($contents, 'use LaravelFreelancerNL\Aranguent\Schema\Blueprint;'))->toBeTrue();
    expect(str_contains($contents, 'use LaravelFreelancerNL\Aranguent\Facades\Schema;'))->toBeTrue();

    unlink($migrationFiles[0]);
});


test('make:migration --edge=edges', function () use ($migrationPath) {
    $this->artisan('make:migration --edge=edges')
        ->expectsQuestion('What should the migration be named?', 'create_edges_table')
        ->assertExitCode(0);

    $migrationFiles = glob($migrationPath.'/*'.'create_edges_table.php');

    $contents = file_get_contents($migrationFiles[0]);

    expect(str_contains($contents, 'use LaravelFreelancerNL\Aranguent\Schema\Blueprint;'))->toBeTrue();
    expect(str_contains($contents, 'use LaravelFreelancerNL\Aranguent\Facades\Schema;'))->toBeTrue();

    unlink($migrationFiles[0]);
});