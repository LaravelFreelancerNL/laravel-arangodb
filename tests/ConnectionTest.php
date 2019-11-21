<?php

use Illuminate\Support\Fluent as IlluminateFluent;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use Mockery as M;

class ConnectionTest extends TestCase
{
    public function tearDown() : void
    {
        M::close();
    }

    /**
     * test connection.
     * @test
     */
    public function connection()
    {
        $this->createDatabase();

        $this->assertInstanceOf('LaravelFreelancerNL\Aranguent\Connection', $this->connection);
    }

    /**
     * begin transaction.
     * @test
     */
    public function begin_transaction()
    {
        $this->assertEquals(0, $this->connection->transactionLevel());
        $this->assertEmpty($this->connection->getTransactionCommands());

        $this->connection->beginTransaction();

        $this->assertEquals(1, $this->connection->transactionLevel());
        $this->assertArrayHasKey(1, $this->connection->getTransactionCommands());
        $this->assertEmpty($this->connection->getTransactionCommands()[1]);
    }

    /**
     * add a command to the transaction.
     * @test
     */
    public function add_transaction_command()
    {
        $command = new IlluminateFluent([
            'name' => 'testCommandQuery',
            'command' => "db._query('INSERT {\"name\": \"Robert\", \"surname\": \"Baratheon\", \"alive\": @value, \"traits\": [\"A\",\"H\",\"C\"] } INTO migrations', {'value' : true});",
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

    /**
     * compile action.
     * @test
     */
    public function compile_action()
    {
        $command = new IlluminateFluent([
            'name' => 'testCommandQuery',
            'command' => "db._query('INSERT {\"name\": \"Robert\", \"surname\": \"Baratheon\", \"alive\": false, \"traits\": [\"A\",\"H\",\"C\"] } INTO Characters', {'value' : 1});",
            'collections' => ['write' => 'Characters'],
        ]);
        $this->connection->beginTransaction();
        $action = $this->connection->compileTransactionAction();

        $this->assertEquals("function () { var db = require('@arangodb').db;  }", $action);

        $this->connection->addTransactionCommand($command);
        $action = $this->connection->compileTransactionAction();

        $this->assertEquals("function () { var db = require('@arangodb').db; db._query('INSERT {\"name\": \"Robert\", \"surname\": \"Baratheon\", \"alive\": false, \"traits\": [\"A\",\"H\",\"C\"] } INTO Characters', {'value' : 1}); }", $action);
    }

    /**
     * commit fails if committed too soon.
     * @test
     */
    public function commit_fails_if_committed_too_soon()
    {
        $this->expectExceptionMessage('Transaction committed before starting one.');
        $this->connection->commit();

        $this->connection->beginTransaction();

        $this->expectExceptionMessage('Cannot commit an empty transaction.');
        $this->connection->commit();
    }

    /**
     * commit a transaction.
     * @test
     */
    public function commit_a_transaction()
    {
        $command1 = new IlluminateFluent([
            'name' => 'testCommandQuery',
            'command' => "db._query('INSERT {\"name\": \"Robert\", \"surname\": \"Baratheon\", \"alive\": @value, \"traits\": [\"A\",\"H\",\"C\"] } INTO migrations', {'value' : true});",
            'collections' => ['write' => 'migrations'],
        ]);
        $command2 = new IlluminateFluent([
            'name' => 'testCommandQuery',
            'command' => "db._query('FOR c IN migrations RETURN c');",
            'collections' => ['read' => 'migrations'],
        ]);

        $this->connection->beginTransaction();

        $this->connection->addTransactionCommand($command1);
        $this->connection->addTransactionCommand($command2);

        $results = $this->connection->commit();

        $this->assertTrue($results);
    }

    /**
     * add query to transaction.
     * @test
     */
    public function add_query_to_transaction()
    {
        $query = '
            FOR u IN users
                FOR p IN @@readCollection
                    FILTER u._key == p.recommendedBy
                    INSERT { 
                        _from: u._id,
                        _to: p._id
                     } IN @@insertCollection
            ';
        $bindings = [
            '@@readCollection' => 'products',
            '@@insertCollection' => 'recommendations',
        ];

        $result = $this->connection->addQueryToTransaction($query, $bindings);

        $this->assertInstanceOf(IlluminateFluent::class, $result);
        $this->assertEquals('aqlQuery', $result->name);
        $this->assertStringContainsString('db._query', $result->command);
        $this->assertIsArray($result->collections['read']);
        $this->assertIsArray($result->collections['write']);
    }

    /**
     * collection extraction from transactional queries.
     * @test
     */
    public function collection_extraction_from_transactional_queries()
    {
        // We're combining multiple queries in one statement which would normally fail when executed.
        // However the goal is to see if the collections are extracted properly.
        $query = "
            FOR u IN users
                FOR p 
                IN
                 @@readCollection
                    FILTER u._key == p.recommendedBy
                    INSERT { 
                        _from: u._id,
                        _to: p._id
                     } IN @@insertCollection
            FOR i IN 1..1000
                INSERT {
                    _key: CONCAT('test', i),
                    name: \"test\",
                    foobar: true
                    } INTO users OPTIONS { ignoreErrors: true }
            FOR v, e, p IN 1..5 OUTBOUND 'circles/A' GRAPH 'traversalGraph'
                FILTER p.vertices[1]._key == \"G\"
                RETURN p                             
        ";
        $bindings = [
            '@@readCollection' => 'products',
            '@@insertCollection' => 'recommendations',
        ];

        $result = $this->connection->addQueryToTransaction($query, $bindings);

        $this->assertInstanceOf(\Illuminate\Support\Fluent::class, $result);
        //Assert that unbound collection are found
        $this->assertContains('users', $result->collections['read']);
        $this->assertContains('circles', $result->collections['read']);

        //Assert that bound collections are found
        $this->assertContains('products', $result->collections['read']);
        $this->assertContains('recommendations', $result->collections['write']);

        //Assert that iterations are not found
        $this->assertNotContains('1', $result->collections['read']);
        $this->assertNotContains('1..1000', $result->collections['read']);
    }

    /**
     * explain a query.
     * @test
     */
    public function explain_a_query()
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
}

class MockArangoConnection extends ArangoDBClient\Connection
{
    public function __construct(array $options = [])
    {
        //
    }
}
