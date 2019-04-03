<?php

namespace Illuminate\Tests\Database;

use Mockery as M;
use Illuminate\Support\Collection;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Schema\Builder;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use LaravelFreelancerNL\Aranguent\Schema\Grammars\Grammar;

class SchemaBuilderTest extends TestCase
{
    public function tearDown() : void
    {
        M::close();
    }

    /**
     * collection has attributes.
     * @test
     */
    public function collection_has_attributes()
    {
        $connection = M::mock(Connection::class);
        $collectionHandler = M::mock(Collection::class);
        $grammar = new Grammar();
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getCollectionHandler')->andReturn($collectionHandler);

        $builder = new Builder($connection);

        $connection->shouldReceive('statement')->once()->andReturn(true);
        $connection->shouldReceive('statement')->once()->andReturn(false);

        $this->assertTrue($builder->hasAttribute('users', ['_id', 'firstname']));
        $this->assertFalse($builder->hasAttribute('users', ['_id', 'not_an_attribute']));
    }
}
