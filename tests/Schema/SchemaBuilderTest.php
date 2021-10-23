<?php

namespace Tests\Schema;

use ArangoClient\ArangoClient;
use ArangoClient\Schema\SchemaManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;
use LaravelFreelancerNL\Aranguent\Schema\Builder;
use LaravelFreelancerNL\Aranguent\Schema\Grammar;
use Mockery as M;
use Tests\Setup\ClassStubs\CustomBlueprint;
use Tests\TestCase;
use TiMacDonald\Log\LogFake;

class SchemaBuilderTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
    }

    public function tearDown(): void
    {
        M::close();
    }

    public function testCreateWithCustomBlueprint()
    {
        $schema = DB::connection()->getSchemaBuilder();
        $schema->blueprintResolver(function ($table, $callback) {
            return new CustomBlueprint($table, $callback);
        });
        $schema->create('characters', function (Blueprint $collection) {
            $this->assertInstanceOf(CustomBlueprint::class, $collection);
        });
    }

    public function testRename()
    {
        $result = Schema::rename('characters', 'people');

        $this->assertTrue($result);
        $this->assertFalse(Schema::hasTable('characters'));
        $this->assertTrue(Schema::hasTable('people'));

        $result = Schema::rename('people', 'characters');
    }



    public function testDropAllTables()
    {
        $initialTables = Schema::getAllTables();

        Schema::dropAllTables();

        $tables = Schema::getAllTables();

        $this->assertEquals(10, count($initialTables));
        $this->assertEquals(0, count($tables));
    }

    public function testCollectionHasColumns()
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

        $this->assertTrue($builder->hasColumn('users', 'firstname'));
        $this->assertFalse($builder->hasColumn('users', 'not_an_attribute'));
    }

    public function testCreateView()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();
        if (! $schemaManager->hasView('search')) {
            Schema::createView('search', []);
        }
        $view = $schemaManager->getView('search');

        $this->assertEquals('search', $view->name);

        $schemaManager->deleteView('search');
    }

    public function testGetView()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();
        if (! $schemaManager->hasView('search')) {
            Schema::createView('search', []);
        }
        $view = Schema::getView('search');

        $this->assertEquals('search', $view->name);

        $schemaManager->deleteView('search');
    }


    public function testGetAllViews()
    {
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

        $this->assertCount(3, $views);
        $this->assertSame('pages', $views[0]->name);
        $this->assertSame('products', $views[1]->name);
        $this->assertSame('search', $views[2]->name);

        $schemaManager->deleteView('search');
        $schemaManager->deleteView('pages');
        $schemaManager->deleteView('products');
    }

    public function testEditView()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();
        if (! $schemaManager->hasView('search')) {
            Schema::createView('search', []);
        }
        Schema::editView('search', ['consolidationIntervalMsec' => 5]);

        $properties = $schemaManager->getViewProperties('search');

        $this->assertEquals(5, $properties->consolidationIntervalMsec);

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


    public function testDropAllViews()
    {
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
    }

    public function testCreateDatabase()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();

        $databaseName = 'aranguent__test_dummy';
        $result = Schema::createDatabase($databaseName);

        $this->assertTrue($result);
        $this->assertTrue($schemaManager->hasDatabase($databaseName));

        $schemaManager->deleteDatabase($databaseName);
    }

    public function testDropDatabaseIfExists()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();

        $databaseName = 'aranguent__test_dummy';
        Schema::createDatabase($databaseName);


        $result = Schema::dropDatabaseIfExists($databaseName);

        $this->assertTrue($result);
        $this->assertFalse($schemaManager->hasDatabase($databaseName));
    }

    public function testDropDatabaseIfExistsNoneExistingDb()
    {
        $schemaManager = $this->connection->getArangoClient()->schema();
        $databaseName = 'aranguent__test_dummy';

        $this->assertFalse($schemaManager->hasDatabase($databaseName));

        $result = Schema::dropDatabaseIfExists($databaseName);

        $this->assertTrue($result);
    }

    public function testGetConnection()
    {
        $this->assertInstanceOf(Connection::class, Schema::getConnection());
    }

    public function testCallToNoneExistingMethod()
    {
        Log::swap(new LogFake());

        Schema::noneExistingMethod('shizzle');

        Log::assertLogged('warning', function ($message, $context) {
            return Str::contains($message, 'noneExistingMethod');
        });
    }
}
