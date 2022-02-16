<?php

use ArangoClient\ArangoClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class);

test('connection is made', function () {
    $connection = DB::connection();

    expect($connection)->toBeInstanceOf('LaravelFreelancerNL\Aranguent\Connection');
});

test('change database name', function () {
    $initialName = $this->connection->getDatabaseName();
    $newName = $initialName . 'New';
    $this->connection->setDatabaseName($newName);
    $currentName = $this->connection->getDatabaseName();

    $this->assertNotEquals($initialName, $currentName);
    expect($currentName)->toEqual($newName);
});

test('explain query', function () {
    $query = '
        FOR i IN 1..1000
            RETURN i
    ';

    $explanation = $this->connection->explain($query);

    $this->assertObjectHasAttribute('plan', $explanation);
});

test('error handling collection not found', function () {
    $this->expectExceptionCode(404);
    DB::table('this_collection_does_not_exist')->get();
});

test('error handling malformed aql', function () {
    $this->expectExceptionCode(400);
    DB::statement('this aql is malformed');
});

test('disconnect', function () {
    $connection = DB::connection();
    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);
    $connection->disconnect();
    expect($connection->getArangoClient())->toBeNull();
});

test('purge', function () {
    $connection = DB::connection();
    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);
    DB::purge();
    expect($connection->getArangoClient())->toBeNull();
});

test('db calls fail after purge', function () {
    DB::purge();
    Schema::hasTable('NotATable');
});

test('reconnect', function () {
    $connection = DB::connection();
    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);
    DB::purge();

    $connection->reconnect();

    $connection = DB::connection();
    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);
});

test('reconnect to different datase', function () {
    $connection = DB::connection();
    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);

    DB::purge();
    $newDatabase = "otherDatabase";
    config()->set('database.connections.arangodb.database', $newDatabase);
    $connection->reconnect();

    $connection = DB::connection();
    expect($connection->getArangoClient()->getDatabase())->toBe($newDatabase);
});

test('reconnect to different datase throws404', function () {
    $connection = DB::connection();
    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);
    DB::purge();

    $newDatabase = "otherDatabase";
    config()->set('database.connections.arangodb.database', $newDatabase);
    $connection->reconnect();

    $this->expectExceptionCode(404);
        Schema::hasTable('dummy');
});
