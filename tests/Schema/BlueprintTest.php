<?php

use ArangoClient\Schema\SchemaManager;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

test('create index', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->index(['name']);
    });
    $name = 'characters_name_persistent';

    $index = $this->schemaManager ->getIndexByName('characters', $name);

    $this->assertEquals($name, $index->name);
});

test('drop index', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->index(['name']);
    });

    Schema::table('characters', function (Blueprint $collection) {
        $collection->dropIndex('characters_name_persistent');
    });

    $searchResult = $this->schemaManager->getIndexByName('characters', 'characters_name_persistent');
    $this->assertFalse($searchResult);
});

test('index names only contains alpha numeric characters', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $indexName = $collection->createIndexName('persistent', ['addresses[*]']);
        $this->assertEquals('characters_addresses_array_persistent', $indexName);
    });
});

test('index names include options', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $options = [
            'unique' => true,
            'sparse' => true
        ];

        $indexName = $collection->createIndexName('persistent', ['address'], $options);

        $this->assertEquals('characters_address_persistent_unique_sparse', $indexName);
    });
});

test('create index with array', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->index(['addresses[*]']);
    });
    Schema::table('characters', function (Blueprint $collection) {
        $collection->dropIndex('characters_addresses_array_persistent');
    });
});

test('drop index with array', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->index(['addresses[*]']);
    });

    Schema::table('characters', function (Blueprint $collection) {
        $collection->dropIndex('characters_addresses_array_persistent');
    });

    $searchResult = $this->schemaManager->getIndexByName('characters', 'characters_addresses_array_persistent');
    $this->assertFalse($searchResult);
});

test('attribute ignore additional arguments', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->string('token', 64)->index();

        $commands = $collection->getCommands();
        $this->assertEquals(2, count($commands));
        $this->assertEquals(1, count($commands[1]['columns']));
    });
});

test('foreign id is excluded', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->foreignId('user_id')->index();

        $commands = $collection->getCommands();

        $this->assertEquals(2, count($commands));
        $this->assertEquals('ignore', $commands[0]['name']);
        $this->assertEquals('foreignId', $commands[0]['method']);
        $this->assertEquals('user_id', $commands[1]['columns'][0]);
    });
});

test('default', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->string('name')->default('John Doe');

        $commands = $collection->getCommands();

        $this->assertEquals(2, count($commands));
        $this->assertEquals('ignore', $commands[0]['name']);
        $this->assertEquals('string', $commands[0]['method']);
        $this->assertEquals('ignore', $commands[1]['name']);
        $this->assertEquals('default', $commands[1]['method']);
    });
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\CharactersSeeder::class]);
}
