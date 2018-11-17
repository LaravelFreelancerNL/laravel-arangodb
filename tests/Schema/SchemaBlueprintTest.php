<?php

namespace Illuminate\Tests\Database;

use ArangoDBClient\CollectionHandler;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Schema\Grammars\Grammar;
use Mockery as M;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;


class SchemaBlueprintTest extends TestCase
{
    public function tearDown()
    {
        M::close();
    }

    /**
     * drop an index
     * @test
     */
    function drop_an_index()
    {
        $connection = M::mock(Connection::class);
        $collectionHandler = M::mock(CollectionHandler::class);
        $grammar = M::mock(Grammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once();
        $connection->shouldReceive('pretending')->twice();
        $collectionHandler->shouldReceive('index')->once();
        $collectionHandler->shouldReceive('getIndexes')->once()->andReturn([
            "error" => false,
            "code" => 200,
            "indexes" => [
                0 => [
                    "fields" => [
                        0 => "_key"
                    ],
                    "id" => "migrations/0",
                    "selectivityEstimate" => 1,
                    "sparse" => false,
                    "type" => "primary",
                    "unique" => true,
                ],
                1 => [
                    "deduplicate" => true,
                    "fields" => [
                        0 => "bar",
                        1 => "foo"
                    ],
                    "id" => "migrations/2144618",
                    "selectivityEstimate" => 1,
                    "sparse" => false,
                    "type" => "hash",
                    "unique" => false,
                ],
                2 => [
                    "deduplicate" => true,
                    "fields" => [
                        0 => "foo"
                    ],
                    "id" => "migrations/2144621",
                    "selectivityEstimate" => 1,
                    "sparse" => false,
                    "type" => "skiplist",
                    "unique" => false,
                ]
            ]
        ]);

        $blueprint = new Blueprint('SchemaBlueprintTestCollection', $collectionHandler);
        $blueprint->index(['foo', 'bar'], 'skiplist');
        $blueprint->dropIndex(['foo', 'bar'], 'skiplist');

        $blueprint->build($connection, $grammar);
    }
}