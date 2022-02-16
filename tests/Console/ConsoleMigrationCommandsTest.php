<?php

use Mockery as M;

beforeEach(function () {
    $this->packageMigrationPath = __DIR__ . '/../Setup/Database/Migrations';
    $this->aranguentMigrationStubPath = __DIR__ . '/../../stubs';
    $this->laravelMigrationPath = base_path() . '/database/migrations';

    // Clear the make migration test stubs
    array_map('unlink', array_filter((array) glob($this->laravelMigrationPath . '/*')));
});

afterEach(function () {
    M::close();
});

/**
 * migrate.
 *
 */
test('migrate', function () {
    $path = 'migrations';

    $this->artisan('migrate', ['--force' => true, '--path' => $path])->run();
});
