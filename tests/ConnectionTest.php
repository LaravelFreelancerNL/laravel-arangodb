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

    public function testBeginTransaction()
    {
        $this->assertEquals(0, $this->connection->transactionLevel());
        $this->assertEmpty($this->connection->getTransactionCommands());

        $this->connection->beginTransaction();

        $this->assertEquals(1, $this->connection->transactionLevel());
        $this->assertArrayHasKey(1, $this->connection->getTransactionCommands());
        $this->assertEmpty($this->connection->getTransactionCommands()[1]);
    }

    public function testAddTransactionCommand()
    {
        $query = [
            "name" => "Robert",
            "surname" => "Baratheon",
            "alive" => '@value',
            "traits" => ["A","H","C"]
        ];

        $command = new IlluminateFluent([
            'name'        => 'testCommandQuery',
            'command'     => "db._query('INSERT '" . json_encode($query) . "' INTO migrations', {'value' : true});",
            'collections' => ['write' => 'migrations'],
        ]);

        $this->connection->beginTransaction();

        $this->connection->addTransactionCommand($command);

        $commands = collect($this->connection->getTransactionCommands()[$this->connection->transactionLevel()]);
        $command = $commands->first();

        $this->assertEquals(1, $commands->count());
        $this->assertInstanceOf(IlluminateFluent::class, $command);
        $this->assertNotEmpty($command->name);
        $this->assertNotEmpty($command->command);
    }

    public function testCompileAction()
    {
        $query = [
            "name" => "Robert",
            "surname" => "Baratheon",
            "alive" => false,
            "traits" => ["A","H","C"]
        ];

        $command = new IlluminateFluent([
            'name'        => 'testCommandQuery',
            'command'     => "db._query('INSERT " . json_encode($query) . " INTO Characters', {'value' : 1});",
            'collections' => ['write' => 'Characters'],
        ]);
        $this->connection->beginTransaction();
        $action = $this->connection->compileTransactionAction();

        $this->assertEquals("function () { var db = require('@arangodb').db;  }", $action);

        $this->connection->addTransactionCommand($command);
        $action = $this->connection->compileTransactionAction();

        $this->assertEquals(
            "function () { var db = require('@arangodb').db; db._query("
            . "'INSERT " . json_encode($query) . " INTO Characters', {'value' : 1}); }",
            $action
        );
    }

    public function testCommitFailsIfCommittedTooSoon()
    {
        $this->expectExceptionMessage('Transaction committed before starting one.');
        $this->connection->commit();

        $this->connection->beginTransaction();

        $this->expectExceptionMessage('Cannot commit an empty transaction.');
        $this->connection->commit();
    }

    public function testCommitTransaction()
    {
        $query = [
            "name" => "Robert",
            "surname" => "Baratheon",
            "alive" => true,
            "traits" => ["A","H","C"]
        ];

        $command1 = new IlluminateFluent([
            'name'        => 'testCommandQuery',
            'command'     => "db._query('INSERT " . json_encode($query) . " INTO migrations');",
            'collections' => ['write' => 'migrations'],
        ]);
        $command2 = new IlluminateFluent([
            'name'        => 'testCommandQuery',
            'command'     => "db._query('FOR c IN migrations RETURN c');",
            'collections' => ['read' => 'migrations'],
        ]);

        $this->connection->beginTransaction();

        $this->connection->addTransactionCommand($command1);
        $this->connection->addTransactionCommand($command2);

        $results = $this->connection->commit();

        $this->assertTrue($results);
    }


    public function testAddQueryToTransaction()
    {
        $aqb = new QueryBuilder();
        $aqb = $aqb->let('values', [1,2,3,4])
            ->for('value', 'values')
            ->insert('value', 'teams')
            ->return('NEW._key')
            ->get();

        $result = $this->connection->addQueryToTransaction($aqb->query, $aqb->binds, $aqb->collections);

        $this->assertEquals('teams', $result['collections']['write'][0]);
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
