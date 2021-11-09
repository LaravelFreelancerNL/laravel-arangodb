<?php

namespace Tests;

use ArangoClient\ArangoClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConnectionTest extends TestCase
{
    public function testConnectionIsMade()
    {
        $connection = DB::connection();

        $this->assertInstanceOf('LaravelFreelancerNL\Aranguent\Connection', $connection);
    }

    public function testChangeDatabaseName()
    {
        $initialName = $this->connection->getDatabaseName();
        $newName = $initialName . 'New';
        $this->connection->setDatabaseName($newName);
        $currentName = $this->connection->getDatabaseName();

        $this->assertNotEquals($initialName, $currentName);
        $this->assertEquals($newName, $currentName);
    }

    public function testExplainQuery()
    {
        $query = '
            FOR i IN 1..1000
                RETURN i
        ';

        $explanation = $this->connection->explain($query);

        $this->assertObjectHasAttribute('plan', $explanation);
    }

    public function testErrorHandlingCollectionNotFound()
    {
        $this->expectExceptionCode(404);
        DB::table('this_collection_does_not_exist')->get();
    }

    public function testErrorHandlingMalformedAql()
    {
        $this->expectExceptionCode(400);
        DB::statement('this aql is malformed');
    }

    public function testDisconnect()
    {
        $connection = DB::connection();
        $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());
        $connection->disconnect();
        $this->assertNull($connection->getArangoClient());
    }

    public function testPurge()
    {
        $connection = DB::connection();
        $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());
        DB::purge();
        $this->assertNull($connection->getArangoClient());
    }

    public function testDbCallsFailAfterPurge()
    {
        DB::purge();
        Schema::hasTable('NotATable');
    }

    public function testReconnect()
    {
        $connection = DB::connection();
        $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());
        DB::purge();
        $connection->reconnect();
//        $this->assertInstanceOf(ArangoClient::class, $connection->getArangoClient());
    }
}
