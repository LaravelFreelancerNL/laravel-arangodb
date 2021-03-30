<?php

namespace Tests\Schema;

use ArangoClient\ArangoClient;
use ArangoClient\Schema\SchemaManager;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Builder;
use LaravelFreelancerNL\Aranguent\Schema\Grammar;
use Mockery as M;
use Tests\TestCase;

class SchemaBuilderTest extends TestCase
{
    public function tearDown(): void
    {
        M::close();
    }

    public function testCollectionHasAttributes()
    {
        $mockConnection = M::mock(Connection::class);
        $mockArangoClient = M::mock(ArangoClient::class);
        $mockSchemaManager = M::mock(SchemaManager::class);
        $grammar = new Grammar();
        $mockConnection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $mockConnection->shouldReceive('getArangoClient')->andReturn($mockArangoClient);
        $mockArangoClient->shouldReceive('schema')->andReturn($mockSchemaManager);

        $builder = new Builder($mockConnection);

        $mockConnection->shouldReceive('statement')->once()->andReturn(true);
        $mockConnection->shouldReceive('statement')->once()->andReturn(false);

        $this->assertTrue($builder->hasAttribute('users', ['_id', 'firstname']));
        $this->assertFalse($builder->hasAttribute('users', ['_id', 'not_an_attribute']));
    }

    public function testCreateView()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();
        if (! $schemaManager->hasView('search')) {
            Schema::createView('search', []);
        }
        $view = $schemaManager->getView('search');

        $this->assertEquals('search', $view['name']);

        $schemaManager->deleteView('search');
    }

    public function testGetView()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();
        if (! $schemaManager->hasView('search')) {
            Schema::createView('search', []);
        }
        $view = $schemaManager->getView('search');

        $this->assertEquals('search', $view['name']);

        $schemaManager->deleteView('search');
    }

    public function testEditView()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();
        if (! $schemaManager->hasView('search')) {
            Schema::createView('search', []);
        }
        Schema::editView('search', ['consolidationIntervalMsec' => 5]);

        $properties = $schemaManager->getViewProperties('search');

        $this->assertEquals(5, $properties['consolidationIntervalMsec']);

        $schemaManager->deleteView('search');
    }

    public function testRenameView()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();
        if (! $schemaManager->hasView('search')) {
            Schema::createView('search', []);
        }
        Schema::renameView('search', 'find');

        $view = $schemaManager->getView('find');
        $schemaManager->deleteView('find');

        $this->expectExceptionMessage('collection or view not found');
        $schemaManager->getView('search');

        $this->assertEquals('find', $view->getName());

        $schemaManager->deleteView('search');
    }

    public function testDropView()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();
        if (! $schemaManager->hasView('search')) {
            Schema::createView('search', []);
        }
        Schema::dropView('search');

        $this->expectExceptionMessage('collection or view not found');

        $schemaManager->getView('search');
    }

    public function testDropAllTables()
    {
        $initialCollections = Schema::getAllCollections();

        Schema::dropAllTables();

        $collections = Schema::getAllCollections();

        $this->assertEquals(10, count($initialCollections));
        $this->assertEquals(0, count($collections));

        $this->migrate();
    }
}
