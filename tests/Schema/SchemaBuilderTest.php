<?php

use ArangoClient\ArangoClient;
use ArangoClient\Schema\SchemaManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\QueryException;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;
use LaravelFreelancerNL\Aranguent\Schema\Builder;
use LaravelFreelancerNL\Aranguent\Schema\Grammar;
use Mockery as M;
use Tests\Setup\ClassStubs\CustomBlueprint;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    M::close();
});

test('create with custom blueprint', function () {
    $schema = DB::connection()->getSchemaBuilder();
    $schema->blueprintResolver(function ($table, $callback) {
        return new CustomBlueprint($table, $callback);
    });
    $schema->create('characters', function (Blueprint $collection) {
        $this->assertInstanceOf(CustomBlueprint::class, $collection);
    });
});

test('has table', function () {
    $this->assertTrue(Schema::hasTable('locations'));
    $this->assertFalse(Schema::hasTable('dummy'));
});

test('has table throws on none existing database', function () {
    DB::purge();
    $newDatabase = "otherDatabase";
    config()->set('database.connections.arangodb.database', $newDatabase);

    $this->expectException(QueryException::class);

    Schema::hasTable('dummy');
});

test('rename', function () {
    $result = Schema::rename('characters', 'people');

    $this->assertTrue($result);
    $this->assertFalse(Schema::hasTable('characters'));
    $this->assertTrue(Schema::hasTable('people'));

    $result = Schema::rename('people', 'characters');
});

test('drop all tables', function () {
    $initialTables = Schema::getAllTables();

    Schema::dropAllTables();

    $tables = Schema::getAllTables();

    $this->assertEquals(10, count($initialTables));
    $this->assertEquals(0, count($tables));
});

test('collection has columns', function () {
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

    $this->assertTrue($builder->hasColumn('users', 'firstname'));
    $this->assertFalse($builder->hasColumn('users', 'not_an_attribute'));
});

test('create view', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (! $schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    $view = $schemaManager->getView('search');

    $this->assertEquals('search', $view->name);

    $schemaManager->deleteView('search');
});

test('get view', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (! $schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    $view = Schema::getView('search');

    $this->assertEquals('search', $view->name);

    $schemaManager->deleteView('search');
});

test('get all views', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (! $schemaManager->hasView('pages')) {
        Schema::createView('pages', []);
    }
    if (! $schemaManager->hasView('products')) {
        Schema::createView('products', []);
    }
    if (! $schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }

    $views = Schema::getAllViews();

    $this->assertCount(4, $views);
    $this->assertSame('house_view', $views[0]->name);
    $this->assertSame('pages', $views[1]->name);
    $this->assertSame('products', $views[2]->name);
    $this->assertSame('search', $views[3]->name);

    $schemaManager->deleteView('search');
    $schemaManager->deleteView('pages');
    $schemaManager->deleteView('products');
});

test('edit view', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (! $schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    Schema::editView('search', ['consolidationIntervalMsec' => 5]);

    $properties = $schemaManager->getViewProperties('search');

    $this->assertEquals(5, $properties->consolidationIntervalMsec);

    $schemaManager->deleteView('search');
});

test('rename view', function () {
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
});

test('drop view', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (! $schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    Schema::dropView('search');

    $this->expectExceptionMessage('collection or view not found');

    $schemaManager->getView('search');
});

test('drop all views', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (! $schemaManager->hasView('products')) {
        Schema::createView('products', []);
    }
    if (! $schemaManager->hasView('pages')) {
        Schema::createView('pages', []);
    }
    Schema::dropAllViews();

    $this->expectExceptionMessage('collection or view not found');
    $schemaManager->getView('products');
    $this->expectExceptionMessage('collection or view not found');
    $schemaManager->getView('search');
});

test('create database', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    $databaseName = 'aranguent__test_dummy';
    $result = Schema::createDatabase($databaseName);

    $this->assertTrue($result);
    $this->assertTrue($schemaManager->hasDatabase($databaseName));

    $schemaManager->deleteDatabase($databaseName);
});

test('drop database if exists', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    $databaseName = 'aranguent__test_dummy';
    Schema::createDatabase($databaseName);


    $result = Schema::dropDatabaseIfExists($databaseName);

    $this->assertTrue($result);
    $this->assertFalse($schemaManager->hasDatabase($databaseName));
});

test('drop database if exists none existing db', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    $databaseName = 'aranguent__test_dummy';

    $this->assertFalse($schemaManager->hasDatabase($databaseName));

    $result = Schema::dropDatabaseIfExists($databaseName);

    $this->assertTrue($result);
});

test('get connection', function () {
    $this->assertInstanceOf(Connection::class, Schema::getConnection());
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
