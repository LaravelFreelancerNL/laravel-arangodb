<?php

namespace Tests\Schema;

use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;
use Tests\TestCase;
use Mockery as M;

class SchemaBlueprintTest extends TestCase
{
    public function tearDown(): void
    {
        M::close();
    }

    public function testCreateIndex()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $collection->index(['name']);
        });
        $name = 'characters_name_persistent';

        $collectionHandler = $this->connection->getCollectionHandler();
        $index = $collectionHandler->getIndex('characters', $name);

        $this->assertEquals($name, $index['name']);
    }

    public function testDropIndex()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $collection->index(['name']);
        });

        Schema::table('characters', function (Blueprint $collection) {
            $collection->dropIndex('characters_name_persistent');
        });
        $collectionHandler = $this->connection->getCollectionHandler();

        $this->expectExceptionMessage('index not found');

        $collectionHandler->getIndex('characters', 'characters_name_persistent');
    }

    public function testIndexNamesOnlyContainsAlphaNumericCharacters()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $indexName = $collection->createIndexName('persistent', ['addresses[*]']);
            $this->assertEquals('characters_addresses_array_persistent', $indexName);
        });
    }

    public function testCreateIndexWithArray()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $collection->index(['addresses[*]']);
        });
        Schema::table('characters', function (Blueprint $collection) {
            $collection->dropIndex('characters_addresses_array_persistent');
        });
    }

    public function testDropIndexWithArray()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $collection->index(['addresses[*]']);
        });

        Schema::table('characters', function (Blueprint $collection) {
            $collection->dropIndex('characters_addresses_array_persistent');
        });
        $collectionHandler = $this->connection->getCollectionHandler();

        $this->expectExceptionMessage('index not found');

        $collectionHandler->getIndex('characters', 'characters_addresses_array_persistent');
    }
}
