<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class,
);

test('output conversion with whole document', function () {
    $result = DB::table('characters')->first();

    expect($result)->toHaveProperty('id');
});

test('output conversion with limited attributes', function () {
    $result = DB::table('characters')->first(['_id', '_key', 'name']);

    expect($result)->toHaveProperty('id');
    expect($result)->toHaveProperties(['id', "_id", "name"]);
});

test('output conversion without key', function () {
    $result = DB::table('characters')->first(['_id','name']);

    expect($result)->not->toHaveProperty('id');
    expect($result)->toHaveProperties(['_id', "name"]);
});

test('get id conversion single attribute', function () {
    $builder = getBuilder();
    $builder = $builder->select('id')->from('users');

    $this->assertSame(
        'FOR userDoc IN users RETURN `userDoc`.`_key`',
        $builder->toSql()
    );
});

test('get id conversion multiple attributes', function () {
    $query = DB::table('characters')->select('id', 'name');

    $result = $query->first();

    $this->assertSame(
        'FOR characterDoc IN characters LIMIT 1 RETURN {id: `characterDoc`.`_key`, name: `characterDoc`.`name`}',
        $query->toSql()
    );

    expect($result)->not->toHaveProperty('_key');
    expect($result)->toHaveProperty('id');
    expect($result)->toHaveProperties(['id', "name"]);
});

test('get id conversion with alias', function () {
    $query = DB::table('characters')->select('id as i', 'name');

    $result = $query->first();

    $this->assertSame(
        'FOR characterDoc IN characters LIMIT 1 RETURN {i: `characterDoc`.`_key`, name: `characterDoc`.`name`}',
        $query->toSql()
    );

    expect($result)->not->toHaveProperty('_key');
    expect($result)->toHaveProperty('i');
    expect($result)->toHaveProperties(['i', "name"]);
});

test('get id conversion with multiple ids', function () {
    $query = DB::table('characters')->select('id', 'id as i', 'name');

    $result = $query->first();

    $this->assertSame(
        'FOR characterDoc IN characters LIMIT 1 RETURN {id: `characterDoc`.`_key`, i: `characterDoc`.`_key`, name: `characterDoc`.`name`}',
        $query->toSql()
    );

    expect($result)->not->toHaveProperty('_key');
    expect($result)->toHaveProperty('id');
    expect($result)->toHaveProperties(['id', "i", "name"]);
});

test('get id conversion with multiple aliases', function () {
    $query = DB::table('characters')->select('id as i', 'id as i2', 'name');

    $result = $query->first();

    $this->assertSame(
        'FOR characterDoc IN characters LIMIT 1 RETURN {i: `characterDoc`.`_key`, i2: `characterDoc`.`_key`, name: `characterDoc`.`name`}',
        $query->toSql()
    );

    expect($result)->not->toHaveProperty('_key');
    expect($result)->toHaveProperties(['i2', "i", "name"]);
});

test('get id conversion with wheres', function () {
    $query = DB::table('characters')
        ->where('id', 'NedStark');


    $result = $query->first();

    $this->assertSame(
        'FOR characterDoc IN characters FILTER `characterDoc`.`_key` == @' . $query->getQueryId() . '_where_1'
        . ' LIMIT 1 RETURN characterDoc',
        $query->toSql()
    );

    expect($result)->not->toHaveProperty('_key');
});

test('model has correct ids', function () {
    $results = Character::all();

    expect($results->first()->id)->toBe('NedStark');
    expect($results->first()->_id)->toBe('characters/NedStark');
    expect($results->first()->_key)->toBeNull();
});
