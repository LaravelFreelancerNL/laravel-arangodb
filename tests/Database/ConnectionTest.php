<?php

declare(strict_types=1);

use ArangoClient\ArangoClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LaravelFreelancerNL\Aranguent\QueryException;

test('connection is made', function () {
    $connection = $this->connection;

    expect($connection)->toBeInstanceOf('LaravelFreelancerNL\Aranguent\Connection');
});

test('change database name', function () {
    $initialName = $this->connection->getDatabaseName();

    $newName = $initialName . 'New';
    $this->connection->setDatabaseName($newName);
    $currentName = $this->connection->getDatabaseName();

    $this->assertNotEquals($initialName, $currentName);
    expect($currentName)->toEqual($newName);

    $this->connection->setDatabaseName($initialName);
});

test('explain query', function () {
    $query = '
        FOR i IN 1..1000
            RETURN i
    ';

    $explanation = $this->connection->explain($query);

    $this->assertObjectHasProperty('plan', $explanation);
});

test('error handling collection not found', function () {
    $this->expectExceptionCode(404);
    DB::table('this_collection_does_not_exist')->get();
});

test('error handling malformed aql', function () {
    $this->expectExceptionCode(400);
    DB::statement('this aql is malformed');
});

/**
 * FIXME: figure out a way to reset the DB connection after the exception is thrown.
 */
//test('disconnect', function () {
//    $connection = DB::connection();
//
//    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);
//
//    $connection->disconnect();
//
//    expect($connection->getArangoClient())->toBeNull();
//});

test('purge', function () {
    $connection = DB::connection();

    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);

    DB::purge();

    expect($connection->getArangoClient())->toBeNull();

    $connection->reconnect();
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

test('reconnect to different database', function () {
    $initialName = $this->connection->getDatabaseName();

    $connection = DB::connection();
    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);

    DB::purge();
    $newDatabase = "otherDatabase";
    config()->set('database.connections.arangodb.database', $newDatabase);
    $connection->reconnect();

    $connection = DB::connection();
    expect($connection->getArangoClient()->getDatabase())->toBe($newDatabase);

    config()->set('database.connections.arangodb.database', $initialName);
    $connection->setDatabaseName($initialName);
});

/**
 * FIXME: figure out a way to reset the DB connection after the exception is thrown.
 */
//test('reconnect to different database throws 404', function () {
//    $initialName = $this->connection->getDatabaseName();
//
//    $connection = DB::connection();
//    expect($connection->getArangoClient())->toBeInstanceOf(ArangoClient::class);
//    DB::purge();
//
//    $newDatabase = "otherDatabase";
//    config()->set('database.connections.arangodb.database', $newDatabase);
//    $connection->reconnect();
//
//    Schema::hasTable('dummy');
//})->throws(QueryException::class);
