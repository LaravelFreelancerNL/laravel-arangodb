<?php

namespace Tests\Schema;

use Illuminate\Support\Collection;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Builder;
use LaravelFreelancerNL\Aranguent\Schema\Grammar;
use Tests\TestCase;
use Mockery as M;

class SchemaBuilderTest extends TestCase
{
    public function tearDown(): void
    {
        M::close();
    }

    public function testCollectionHasAttributes()
    {
        $connection = M::mock(Connection::class);
        $collectionHandler = M::mock(Collection::class);
        $grammar = new Grammar();
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getCollectionHandler')->andReturn($collectionHandler);
        $connection->shouldReceive('getViewHandler')->andReturn($collectionHandler);
        $connection->shouldReceive('getGraphHandler')->andReturn($collectionHandler);

        $builder = new Builder($connection);

        $connection->shouldReceive('statement')->once()->andReturn(true);
        $connection->shouldReceive('statement')->once()->andReturn(false);

        $this->assertTrue($builder->hasAttribute('users', ['_id', 'firstname']));
        $this->assertFalse($builder->hasAttribute('users', ['_id', 'not_an_attribute']));
    }

    public function testCreateView()
    {
        Schema::createView('search', []);

        $viewHandler = $this->connection->getViewHandler();
        $view = $viewHandler->get('search');
        $viewHandler->drop('search');

        $this->assertEquals('search', $view->getName());
    }

    public function testGetView()
    {
        Schema::createView('search', []);
        $view = Schema::getView('search');

        $viewHandler = $this->connection->getViewHandler();
        $viewHandler->drop('search');

        $this->assertEquals('search', $view['name']);
    }

    public function testEditView()
    {
        Schema::createView('search', []);
        Schema::editView('search', ['consolidationIntervalMsec' => 5]);

        $viewHandler = $this->connection->getViewHandler();
        $properties = $viewHandler->properties('search');
        $viewHandler->drop('search');

        $this->assertEquals(5, $properties['consolidationIntervalMsec']);
    }

    public function testRenameView()
    {
        Schema::createView('search', []);
        Schema::renameView('search', 'find');

        $viewHandler = $this->connection->getViewHandler();

        $view = $viewHandler->get('find');
        $viewHandler->drop('find');
        $this->expectExceptionMessage('collection or view not found');
        $viewHandler->get('search');

        $this->assertEquals('find', $view->getName());
    }

    public function testDropView()
    {
        Schema::createView('search', []);
        Schema::dropView('search');

        $this->expectExceptionMessage('collection or view not found');

        $viewHandler = $this->connection->getViewHandler();
        $viewHandler->get('search');
    }
}
