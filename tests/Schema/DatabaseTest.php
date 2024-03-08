<?php

test('createDatabase', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    $databaseName = 'aranguent__test_dummy';
    $result = Schema::createDatabase($databaseName);

    expect($result)->toBeTrue();
    expect($schemaManager->hasDatabase($databaseName))->toBeTrue();

    $schemaManager->deleteDatabase($databaseName);

    refreshDatabase();
});

test('dropDatabaseIfExists', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    $databaseName = 'aranguent__test_dummy';
    Schema::createDatabase($databaseName);

    $result = Schema::dropDatabaseIfExists($databaseName);

    expect($result)->toBeTrue();
    expect($schemaManager->hasDatabase($databaseName))->toBeFalse();

    refreshDatabase();
});

test('dropDatabaseIfExists on none-existing db', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    $databaseName = 'aranguent__test_dummy';

    expect($schemaManager->hasDatabase($databaseName))->toBeFalse();

    $result = Schema::dropDatabaseIfExists($databaseName);

    expect($result)->toBeTrue();

    refreshDatabase();
});
