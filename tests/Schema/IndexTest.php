<?php

declare(strict_types=1);

use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;

beforeEach(function () {
    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

test('create index', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->index(['name']);
    });
    $name = 'characters_name_persistent';

    $index = $this->schemaManager->getIndexByName('characters', $name);

    expect($index->name)->toEqual($name);
});

test('drop index', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->index(['name']);
    });

    Schema::table('characters', function (Blueprint $table) {
        $table->dropIndex('characters_name_persistent');
    });

    $searchResult = $this->schemaManager->getIndexByName('characters', 'characters_name_persistent');
    expect($searchResult)->toBeFalse();
});

test('index names only contains alpha numeric characters', function () {
    Schema::table('characters', function (Blueprint $table) {
        $indexName = $table->createIndexName('persistent', ['addresses[*]']);
        expect($indexName)->toEqual('characters_addresses_array_persistent');
    });
});

test('index names include options', function () {
    Schema::table('characters', function (Blueprint $table) {
        $options = [
            'unique' => true,
            'sparse' => true,
        ];

        $indexName = $table->createIndexName('persistent', ['address'], $options);

        expect($indexName)->toEqual('characters_address_persistent_unique_sparse');
    });
});

test('create index with array', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->index(['addresses[*]']);
    });
    Schema::table('characters', function (Blueprint $table) {
        $table->dropIndex('characters_addresses_array_persistent');
    });
});

test('drop index with array', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->index(['addresses[*]']);
    });

    Schema::table('characters', function (Blueprint $table) {
        $table->dropIndex('characters_addresses_array_persistent');
    });

    $searchResult = $this->schemaManager->getIndexByName('characters', 'characters_addresses_array_persistent');
    expect($searchResult)->toBeFalse();
});

test('invertedIndex & dropInvertedIndex', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->invertedIndex(
            ['name'],
            'inv-ind',
            [
                'searchField' => true,
                'includeAllFields' => true
            ]
        );
    });
    $name = 'inv-ind';

    $index = $this->schemaManager->getIndexByName('characters', $name);

    expect($index->name)->toEqual($name);

    Schema::table('characters', function (Blueprint $table) use ($name) {
        $table->dropInvertedIndex($name);
    });
});

test('persistentIndex', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->persistentIndex(['name']);
    });
    $name = 'characters_name_persistent';

    $index = $this->schemaManager->getIndexByName('characters', $name);

    expect($index->name)->toEqual($name);

    Schema::table('characters', function (Blueprint $table) use ($name) {
        $table->dropIndex($name);
    });
});

test('primary & dropPrimary', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->primary(['id', 'name']);
    });

    $name = 'characters_key_name_persistent_unique';

    $index = $this->schemaManager->getIndexByName('characters', $name);

    expect($index->name)->toEqual($name);

    Schema::table('characters', function (Blueprint $table) use ($name) {
        $table->dropPrimary($name);
    });
});

test('Skip persistent index on id', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->primary('id');
    });

    $indexes = $this->schemaManager->getIndexes('characters');

    expect(count($indexes))->toBe(1);
});


test('unique & dropUnique', function () {
    Schema::table('users', function (Blueprint $table) {
        $table->unique(['email']);
    });

    $name = 'users_email_persistent_unique';

    $index = $this->schemaManager->getIndexByName('users', $name);

    expect($index->name)->toEqual($name);

    Schema::table('users', function (Blueprint $table) use ($name) {
        $table->dropUnique($name);
    });
});

test('spatialIndex & dropSpatialIndex', function () {
    Schema::table('locations', function (Blueprint $table) {
        $table->spatialIndex(columns: 'coordinate', indexOptions: ['geoJson' => true]);
    });

    $name = 'locations_coordinate_geo_geojson';

    $index = $this->schemaManager->getIndexByName('locations', $name);

    expect($index->name)->toEqual($name);

    Schema::table('locations', function (Blueprint $table) use ($name) {
        $table->dropSpatialIndex($name);
    });
});

test('geoIndex', function () {
    $name = 'testGeoIndex';

    Schema::table('locations', function (Blueprint $table) use ($name) {
        $table->geoIndex(columns: 'coordinate', name: $name, indexOptions: ['geoJson' => true]);
    });

    $index = $this->schemaManager->getIndexByName('locations', $name);

    expect($index->name)->toEqual($name);

    Schema::table('locations', function (Blueprint $table) use ($name) {
        $table->dropSpatialIndex($name);
    });
});

test('ttlIndex & dropTtlIndex', function () {
    Schema::table('password_reset_tokens', function (Blueprint $table) {
        $table->ttlIndex('created_at', 3600);
    });

    $name = 'password_reset_tokens_created_at_ttl_expireafter';

    $index = $this->schemaManager->getIndexByName('password_reset_tokens', $name);

    expect($index->name)->toEqual($name);

    Schema::table('password_reset_tokens', function (Blueprint $table) use ($name) {
        $table->dropTtlIndex($name);
    });
});

test('indexCommand without type', function () {
    Schema::table('password_reset_tokens', function (Blueprint $table) {
        $table->ttlIndex('created_at', 3600);
    });

    $name = 'password_reset_tokens_created_at_ttl_expireafter';

    $index = $this->schemaManager->getIndexByName('password_reset_tokens', $name);

    expect($index->name)->toEqual($name);

    Schema::table('password_reset_tokens', function (Blueprint $table) use ($name) {
        $table->dropTtlIndex($name);
    });
});
