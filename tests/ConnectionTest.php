<?php

use ArangoClient\ArangoClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class);

test('connection is made', function () {
    $connection = DB::connection();

    $this->assertInstanceOf('LaravelFreelancerNL\Aranguent\Connection', $connection);
});

test('change database name', function () {
    $initialName = $this->connection->getDatabaseName();
    $newName = $initialName . 'New';
    $this->connection->setDatabaseName($newName);
    $currentName = $this->connection->getDatabaseName();

    $this->assertNotEquals($initialName, $currentName);
    $this->assertEquals($newName, $currentName);
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
    $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());
    $connection->disconnect();
    $this->assertNull($connection->getArangoClient());
});

test('purge', function () {
    $connection = DB::connection();
    $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());
    DB::purge();
    $this->assertNull($connection->getArangoClient());
});

test('db calls fail after purge', function () {
    DB::purge();
    Schema::hasTable('NotATable');
});

test('reconnect', function () {
    $connection = DB::connection();
    $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());
    DB::purge();

    $connection->reconnect();

    $connection = DB::connection();
    $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());
});

test('reconnect to different datase', function () {
    $connection = DB::connection();
    $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());

    DB::purge();
    $newDatabase = "otherDatabase";
    config()->set('database.connections.arangodb.database', $newDatabase);
    $connection->reconnect();

    $connection = DB::connection();
    $this->assertSame($newDatabase, $connection->getArangoClient()->getDatabase());
});

test('reconnect to different datase throws404', function () {
    $connection = DB::connection();
    $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());
    DB::purge();

    $newDatabase = "otherDatabase";
    config()->set('database.connections.arangodb.database', $newDatabase);
    $connection->reconnect();

    $this->expectExceptionCode(404);
        Schema::hasTable('dummy');
});
