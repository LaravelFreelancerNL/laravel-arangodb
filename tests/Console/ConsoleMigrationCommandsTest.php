<?php

use Tests\TestCase;

uses(
    TestCase::class,
);

/**
 * migrate.
 *
 */
test('migrate', function () {
    $path = 'migrations';

    $this->artisan('migrate', ['--force' => true, '--path' => $path])->run();
});
