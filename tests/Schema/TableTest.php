<?php

use LaravelFreelancerNL\Aranguent\Exceptions\QueryException;
use  LaravelFreelancerNL\Aranguent\Schema\Blueprint;

test('creating table with increments', function () {
    $schema = DB::connection()->getSchemaBuilder();

    $schema->create('white_walkers', function (Blueprint $table) use (& $creating) {
        $table->increments('id');
    });

    $schemaManager = $this->connection->getArangoClient()->schema();

    $collectionProperties = $schemaManager->getCollectionProperties('white_walkers');

    expect($collectionProperties->keyOptions->type)->toBe('autoincrement');

    Schema::drop('white_walkers');
});

test('creating table with autoIncrement', function () {
    $schema = DB::connection()->getSchemaBuilder();

    $schema->create('white_walkers', function (Blueprint $table) use (& $creating) {
        $table->string('id')->autoIncrement();
    });

    $schemaManager = $this->connection->getArangoClient()->schema();

    $collectionProperties = $schemaManager->getCollectionProperties('white_walkers');

    expect($collectionProperties->keyOptions->type)->toBe('autoincrement');

    Schema::drop('white_walkers');
});
test('creating table with autoIncrement offset', function () {
    $schema = DB::connection()->getSchemaBuilder();

    $schema->create('white_walkers', function (Blueprint $table) use (& $creating) {
        $table->string('id')->autoIncrement()->from(5);
    });

    $schemaManager = $this->connection->getArangoClient()->schema();

    $collectionProperties = $schemaManager->getCollectionProperties('white_walkers');

    expect($collectionProperties->keyOptions->type)->toBe('autoincrement');

    Schema::drop('white_walkers');
});

test('create table with uuid key generator', function () {
    $schema = DB::connection()->getSchemaBuilder();

    $schema->create('white_walkers', function (Blueprint $table) use (& $creating) {
        $table->uuid('id');
    });

    $schemaManager = $this->connection->getArangoClient()->schema();

    $collectionProperties = $schemaManager->getCollectionProperties('white_walkers');

    expect($collectionProperties->keyOptions->type)->toBe('uuid');

    Schema::drop('white_walkers');
});


test('hasTable', function () {
    expect(Schema::hasTable('locations'))->toBeTrue();
    expect(Schema::hasTable('dummy'))->toBeFalse();
});

test('hasTable throws on none existing database', function () {
    DB::purge();
    $newDatabase = 'otherDatabase';
    config()->set('database.connections.arangodb.database', $newDatabase);

    $this->expectException(QueryException::class);

    Schema::hasTable('dummy');
});

test('rename', function () {
    $result = Schema::rename('characters', 'people');

    expect($result)->toBeTrue();
    expect(Schema::hasTable('characters'))->toBeFalse();
    expect(Schema::hasTable('people'))->toBeTrue();

    Schema::rename('people', 'characters');

    refreshDatabase();
});

test('dropAllTables', function () {
    $initialTables = Schema::getAllTables();

    Schema::dropAllTables();

    $tables = Schema::getAllTables();

    expect(count($initialTables))->toEqual(10);
    expect(count($tables))->toEqual(0);

    refreshDatabase();
});
