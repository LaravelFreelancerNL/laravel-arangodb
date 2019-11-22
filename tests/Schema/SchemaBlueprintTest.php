<?php

namespace Illuminate\Tests\Database;

use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use Mockery as M;

class SchemaBlueprintTest extends TestCase
{
    public function tearDown() : void
    {
        M::close();
    }

    public function testCreateIndex()
    {
        Schema::table('users', function (Blueprint $collection) {
            $collection->index(['name']);
        });
        $name = 'users_name_persistent';

        $collectionHandler = $this->connection->getCollectionHandler();
        $index = $collectionHandler->getIndex('users', $name);

        $this->assertEquals($name, $index['name']);
    }

    public function testDropIndex()
    {
        Schema::table('users', function (Blueprint $collection) {
            $collection->index(['name']);
        });

        Schema::table('users', function (Blueprint $collection) {
            $collection->dropIndex('users_name_persistent');
        });
        $collectionHandler = $this->connection->getCollectionHandler();

        $this->expectExceptionMessage('index not found');

        $collectionHandler->getIndex('users', 'users_name_persistent');
    }

}
