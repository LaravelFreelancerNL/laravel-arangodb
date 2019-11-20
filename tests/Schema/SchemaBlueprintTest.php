<?php

namespace Illuminate\Tests\Database;

use Mockery as M;
use ArangoDBClient\CollectionHandler;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;
use LaravelFreelancerNL\Aranguent\Schema\Grammars\Grammar;

class SchemaBlueprintTest extends TestCase
{
    public function tearDown() : void
    {
        M::close();
    }

    /**
     * drop an index.
     * @test
     */
    public function drop_an_index()
    {
        $connection = M::mock(Connection::class);
        $collectionHandler = M::mock(CollectionHandler::class);
        $grammar = M::mock(Grammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once();
        $connection->shouldReceive('pretending')->twice();
        $collectionHandler->shouldReceive('index')->once();
        $collectionHandler->shouldReceive('getIndexes')->once()->andReturn([
            'error' => false,
            'code' => 200,
            'indexes' => [
                0 => [
                    'fields' => [
                        0 => '_key',
                    ],
                    'id' => 'migrations/0',
                    'selectivityEstimate' => 1,
                    'sparse' => false,
                    'type' => 'primary',
                    'unique' => true,
                ],
                1 => [
                    'deduplicate' => true,
                    'fields' => [
                        0 => 'bar',
                        1 => 'foo',
                    ],
                    'id' => 'migrations/2144618',
                    'selectivityEstimate' => 1,
                    'sparse' => false,
                    'type' => 'hash',
                    'unique' => false,
                ],
                2 => [
                    'deduplicate' => true,
                    'fields' => [
                        0 => 'foo',
                    ],
                    'id' => 'migrations/2144621',
                    'selectivityEstimate' => 1,
                    'sparse' => false,
                    'type' => 'skiplist',
                    'unique' => false,
                ],
            ],
        ]);

        $blueprint = new Blueprint('SchemaBlueprintTestCollection', $collectionHandler);
        $blueprint->index(['foo', 'bar'], 'skiplist');
        $blueprint->dropIndex(['foo', 'bar'], 'skiplist');

        $blueprint->build($connection, $grammar);
    }

    /**
     * getIndexType gives correct type based on the algorithm given.
     * @test
     */
    public function get_index_type_gives_correct_type_based_on_the_algorithm_given()
    {
        $collectionHandler = M::mock(CollectionHandler::class);
        $blueprint = new Blueprint('SchemaBlueprintTestCollection', $collectionHandler);

        $type = $blueprint->mapIndexType('HASH');
        $this->assertEquals('hash', $type);

        $type = $blueprint->mapIndexType('BTREE');
        $this->assertEquals('skiplist', $type);

        $type = $blueprint->mapIndexType('RTREE');
        $this->assertEquals('geo', $type);

        $type = $blueprint->mapIndexType(null);
        $this->assertEquals('skiplist', $type);

        $type = $blueprint->mapIndexType('TTL');
        $this->assertEquals('ttl', $type);

        $type = $blueprint->mapIndexType('whatever');
        $this->assertEquals('skiplist', $type);
    }
}
