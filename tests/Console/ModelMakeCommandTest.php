<?php

$testModelData = [
    'name' => 'TestModel',
    'path' => __DIR__ . '/../../vendor/orchestra/testbench-core/laravel/app/Models/TestModel.php',
];

$migrationPath = __DIR__ . '/../../vendor/orchestra/testbench-core/laravel/database/migrations';

afterEach(function () use ($testModelData) {
    unlink($testModelData['path']);
});

test('make:model', function () use ($testModelData) {
    $this->artisan('make:model')
        ->expectsQuestion('What should the model be named?', $testModelData['name'])
        ->expectsQuestion('Would you like any of the following?', '')
        ->assertExitCode(0);

    $contents = file_get_contents($testModelData['path']);

    expect(str_contains($contents, 'use LaravelFreelancerNL\Aranguent\Eloquent\Model;'))->toBeTrue();
});

test('create pivot model', function () use ($testModelData) {
    $this->artisan('make:model --pivot')
        ->expectsQuestion('What should the model be named?', $testModelData['name'])
        ->assertExitCode(0);

    $contents = file_get_contents($testModelData['path']);
    expect(str_contains($contents, 'use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Pivot;'))->toBeTrue();
});

test('create morph-pivot model', function () use ($testModelData) {
    $this->artisan('make:model --morph-pivot')
        ->expectsQuestion('What should the model be named?', $testModelData['name'])
        ->assertExitCode(0);

    $contents = file_get_contents($testModelData['path']);
    expect(substr_count($contents, 'use LaravelFreelancerNL\Aranguent\Eloquent\Relations\MorphPivot;'))->toEqual(1);
});

test('create edge-pivot model', function () use ($testModelData) {
    $this->artisan('make:model --edge-pivot')
        ->expectsQuestion('What should the model be named?', $testModelData['name'])
        ->assertExitCode(0);

    $content = file_get_contents($testModelData['path']);
    expect(str_contains($content, 'use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Pivot;'))->toBeTrue();
    expect(str_contains($content, ' * @property string $_from'))->toBeTrue();
    expect(str_contains($content, ' * @property string $_to'))->toBeTrue();
});

test('create edge-morph-pivot model', function () use ($testModelData) {
    $this->artisan('make:model --edge-morph-pivot')
        ->expectsQuestion('What should the model be named?', $testModelData['name'])
        ->assertExitCode(0);

    $content = file_get_contents($testModelData['path']);
    expect(str_contains($content, 'use LaravelFreelancerNL\Aranguent\Eloquent\Relations\MorphPivot;'))->toBeTrue();
    expect(str_contains($content, ' * @property string $_from'))->toBeTrue();
    expect(str_contains($content, ' * @property string $_to'))->toBeTrue();
});

test('create model with migration', function () use ($testModelData, $migrationPath) {
    $this->artisan('make:model TestModel --migration')
        ->assertExitCode(0);

    $content = file_get_contents($testModelData['path']);
    expect(str_contains($content, 'use LaravelFreelancerNL\Aranguent\Eloquent\Model;'))->toBeTrue();

    $migrationFiles = scandir($migrationPath);

    foreach($migrationFiles as $file) {
        if (in_array($file, ['.', '..', '.gitkeep'])) {
            continue;
        }
        expect(str_contains($file, '_create_test_models_table.php'))->toBeTrue();

        unlink($migrationPath . '/' . $file);
    }
});

test('create model with edge migration', function () use ($testModelData, $migrationPath) {
    $this->artisan('make:model TestModel --migration --edge-pivot')
        ->assertExitCode(0);

    $content = file_get_contents($testModelData['path']);
    expect(str_contains($content, 'use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Pivot;'))->toBeTrue();

    $migrationFiles = scandir($migrationPath);

    foreach($migrationFiles as $file) {
        if (in_array($file, ['.', '..', '.gitkeep'])) {
            continue;
        }
        expect(str_contains($file, '_create_test_models_table.php'))->toBeTrue();

        $content = file_get_contents($migrationPath . '/' . $file);
        expect(str_contains($content, "type' => self::EDGE_COLLECTION,"))->toBeTrue();

        unlink($migrationPath . '/' . $file);
    }
});
