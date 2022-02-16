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

    expect($index->name)->toEqual($name);
});

test('drop index', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->index(['name']);
    });

    Schema::table('characters', function (Blueprint $collection) {
        $collection->dropIndex('characters_name_persistent');
    });

    $searchResult = $this->schemaManager->getIndexByName('characters', 'characters_name_persistent');
    expect($searchResult)->toBeFalse();
});

test('index names only contains alpha numeric characters', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $indexName = $collection->createIndexName('persistent', ['addresses[*]']);
        expect($indexName)->toEqual('characters_addresses_array_persistent');
    });
});

test('index names include options', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $options = [
            'unique' => true,
            'sparse' => true
        ];

        $indexName = $collection->createIndexName('persistent', ['address'], $options);

        expect($indexName)->toEqual('characters_address_persistent_unique_sparse');
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
    expect($searchResult)->toBeFalse();
});

test('attribute ignore additional arguments', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->string('token', 64)->index();

        $commands = $collection->getCommands();
        expect(count($commands))->toEqual(2);
        expect(count($commands[1]['columns']))->toEqual(1);
    });
});

test('foreign id is excluded', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->foreignId('user_id')->index();

        $commands = $collection->getCommands();

        expect(count($commands))->toEqual(2);
        expect($commands[0]['name'])->toEqual('ignore');
        expect($commands[0]['method'])->toEqual('foreignId');
        expect($commands[1]['columns'][0])->toEqual('user_id');
    });
});

test('default', function () {
    Schema::table('characters', function (Blueprint $collection) {
        $collection->string('name')->default('John Doe');

        $commands = $collection->getCommands();

        expect(count($commands))->toEqual(2);
        expect($commands[0]['name'])->toEqual('ignore');
        expect($commands[0]['method'])->toEqual('string');
        expect($commands[1]['name'])->toEqual('ignore');
        expect($commands[1]['method'])->toEqual('default');
    });
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\CharactersSeeder::class]);
}
