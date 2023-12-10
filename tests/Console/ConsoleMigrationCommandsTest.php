<?php

/**
 * migrate.
 *
 */
test('migrate', function () {
    $path = 'migrations';

    $this->artisan('migrate', ['--force' => true, '--path' => $path])->run();
});
