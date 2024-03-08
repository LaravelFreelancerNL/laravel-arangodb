<?php

use LaravelFreelancerNL\Aranguent\Schema\Grammar;
use Mockery as M;

beforeEach(function () {
    $this->grammar = new Grammar();
});

afterEach(function () {
    M::close();
});

test('hasColumn', function () {
    expect(Schema::hasColumn('characters', 'xname'))->toBeFalse();
    expect(Schema::hasColumn('characters', 'name'))->toBeTrue();
});

test('hasColumns', function () {
    expect(Schema::hasColumn('characters', ['name', 'xname']))->toBeFalse();
    expect(Schema::hasColumn('characters', ['name', 'alive']))->toBeTrue();
});

test('attributes are wrapped correctly', function () {
    $attributes = [
        'firstAttribute' => 'firstAttribute',
        '@secondAttribute' => '`@secondAttribute`',
    ];
    foreach (array_keys($attributes) as $attribute) {
        $wrappedAttribute = $this->grammar->wrapBindVar($attribute);
        expect($wrappedAttribute)->toEqual($attributes[$attribute]);
    }
});

test('renameColumn', function () {
    expect(Schema::hasColumn('characters', 'alive'))->toBeTrue();

    $schema = DB::connection()->getSchemaBuilder();
    $schema->table('characters', function (\LaravelFreelancerNL\Aranguent\Schema\Blueprint $table) {
        $table->renameColumn('alive', 'dead');
    });

    expect(Schema::hasColumn('characters', 'alive'))->toBeFalse();
    expect(Schema::hasColumn('characters', 'dead'))->toBeTrue();

    refreshDatabase();
});

test('dropColumn', function () {
    expect(Schema::hasColumn('characters', 'name'))->toBeTrue();

    $schema = DB::connection()->getSchemaBuilder();
    $schema->table('characters', function (\LaravelFreelancerNL\Aranguent\Schema\Blueprint $table) {
        $table->dropColumn('name');
    });

    expect(Schema::hasColumn('characters', 'name'))->toBeFalse();

    refreshDatabase();
});

test('dropColumns', function () {
    expect(Schema::hasColumn('characters', ['name', 'alive']))->toBeTrue();

    $schema = DB::connection()->getSchemaBuilder();
    $schema->table('characters', function (\LaravelFreelancerNL\Aranguent\Schema\Blueprint $table) {
        $table->dropColumn(['name', 'alive']);
    });

    expect(Schema::hasColumn('characters', 'alive'))->toBeFalse();
    expect(Schema::hasColumn('characters', 'name'))->toBeFalse();

    refreshDatabase();
});
