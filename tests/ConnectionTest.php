<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

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
}
