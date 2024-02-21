<?php

test('migrate', function () {
    $this->artisan('migrate', ['--force' => true, '--path' => 'migrations'])->run();
});
