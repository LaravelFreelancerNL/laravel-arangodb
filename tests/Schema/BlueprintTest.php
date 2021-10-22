<?php

namespace Tests\Schema;

use ArangoClient\Schema\SchemaManager;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;
use Tests\TestCase;

class BlueprintTest extends TestCase
{
    protected ?SchemaManager $schemaManager = null;

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

        Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\CharactersSeeder::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaManager = $this->connection->getArangoClient()->schema();
    }

    public function testCreateIndex()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $collection->index(['name']);
        });
        $name = 'characters_name_persistent';

        $index = $this->schemaManager ->getIndexByName('characters', $name);

        $this->assertEquals($name, $index->name);
    }

    public function testDropIndex()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $collection->index(['name']);
        });

        Schema::table('characters', function (Blueprint $collection) {
            $collection->dropIndex('characters_name_persistent');
        });

        $searchResult = $this->schemaManager->getIndexByName('characters', 'characters_name_persistent');
        $this->assertFalse($searchResult);
    }

    public function testIndexNamesOnlyContainsAlphaNumericCharacters()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $indexName = $collection->createIndexName('persistent', ['addresses[*]']);
            $this->assertEquals('characters_addresses_array_persistent', $indexName);
        });
    }

    public function testIndexNamesIncludeOptions()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $options = [
                'unique' => true,
                'sparse' => true
            ];

            $indexName = $collection->createIndexName('persistent', ['address'], $options);

            $this->assertEquals('characters_address_persistent_unique_sparse', $indexName);
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

        $searchResult = $this->schemaManager->getIndexByName('characters', 'characters_addresses_array_persistent');
        $this->assertFalse($searchResult);
    }

    public function testAttributeIgnoreAdditionalArguments()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $collection->string('token', 64)->index();

            $commands = $collection->getCommands();
            $this->assertEquals(2, count($commands));
            $this->assertEquals(1, count($commands[1]['columns']));
        });
    }

    public function testForeignIdIsExcluded()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $collection->foreignId('user_id')->index();

            $commands = $collection->getCommands();

            $this->assertEquals(2, count($commands));
            $this->assertEquals('ignore', $commands[0]['name']);
            $this->assertEquals('foreignId', $commands[0]['method']);
            $this->assertEquals('user_id', $commands[1]['columns'][0]);
        });
    }

    public function testDefault()
    {
        Schema::table('characters', function (Blueprint $collection) {
            $collection->string('name')->default('John Doe');

            $commands = $collection->getCommands();

            $this->assertEquals(2, count($commands));
            $this->assertEquals('ignore', $commands[0]['name']);
            $this->assertEquals('string', $commands[0]['method']);
            $this->assertEquals('ignore', $commands[1]['name']);
            $this->assertEquals('default', $commands[1]['method']);
        });
    }
}
