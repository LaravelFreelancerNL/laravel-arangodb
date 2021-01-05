<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent as IlluminateFluent;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;
use Mockery as M;

class ConnectionTest extends TestCase
{
    public function tearDown(): void
    {
        M::close();
    }

    /**
     * test connection.
     *
     * @test
     */
    public function connection()
    {
        $this->createDatabase();

        $this->assertInstanceOf('LaravelFreelancerNL\Aranguent\Connection', $this->connection);
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
                INSERT {
                    _key: CONCAT(\'test\', i),
                    name: "test",
                    foobar: true
                    } INTO migrations OPTIONS { ignoreErrors: true }
        ';

        $explanation = $this->connection->explain($query);

        $this->assertIsArray($explanation);
        $this->assertArrayHasKey('plan', $explanation);
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
