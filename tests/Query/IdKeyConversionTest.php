<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Models\Character;
use Tests\TestCase;

uses(TestCase::class);

test('output conversion with whole document', function () {
    $result = DB::table('characters')->get();

    $this->assertObjectHasAttribute('id', $result->first());
});

test('output conversion with limited attributes', function () {
    $result = DB::table('characters')->get(['_id', '_key', 'name']);

    $this->assertObjectHasAttribute('id', $result->first());
    $this->assertCount(3, (array)$result->first());
});

test('output conversion without key', function () {
    $result = DB::table('characters')->get(['_id','name']);

    $this->assertObjectNotHasAttribute('id', $result->first());
    $this->assertObjectHasAttribute('_id', $result->first());
    $this->assertCount(2, (array)$result->first());
});

test('get id conversion single attribute', function () {
    $builder = $this->getBuilder();
    $builder = $builder->select('id')->from('users');

    $this->assertSame(
        'FOR userDoc IN users RETURN userDoc._key',
        $builder->toSql()
    );
});

test('get id conversion multiple attributed', function () {
    $query = DB::table('characters')->select('id', 'name');

    $results = $query->get();

    $this->assertSame(
        'FOR characterDoc IN characters RETURN {"id":characterDoc._key,"name":characterDoc.name}',
        $query->toSql()
    );
    $this->assertObjectNotHasAttribute('_key', $results->first());
    $this->assertObjectHasAttribute('id', $results->first());
    $this->assertCount(2, (array)$results->first());
});

test('get id conversion with alias', function () {
    $query = DB::table('characters')->select('id as i', 'name');

    $results = $query->get();

    $this->assertSame(
        'FOR characterDoc IN characters RETURN {"i":characterDoc._key,"name":characterDoc.name}',
        $query->toSql()
    );
    $this->assertObjectNotHasAttribute('_key', $results->first());
    $this->assertObjectNotHasAttribute('id', $results->first());
    $this->assertObjectHasAttribute('i', $results->first());
    $this->assertCount(2, (array)$results->first());
});

test('get id conversion with multiple ids', function () {
    $query = DB::table('characters')->select('id', 'id as i', 'name');

    $results = $query->get();

    $this->assertSame(
        'FOR characterDoc IN characters RETURN {"id":characterDoc._key,"i":characterDoc._key,"name":characterDoc.name}',
        $query->toSql()
    );
    $this->assertObjectNotHasAttribute('_key', $results->first());
    $this->assertObjectHasAttribute('id', $results->first());
    $this->assertObjectHasAttribute('i', $results->first());
    $this->assertCount(3, (array)$results->first());
});

test('get id conversion with multiple aliases', function () {
    $query = DB::table('characters')->select('id as i', 'id as i2', 'name');

    $results = $query->get();

    $this->assertSame(
        'FOR characterDoc IN characters RETURN {"i":characterDoc._key,"i2":characterDoc._key,"name":characterDoc.name}',
        $query->toSql()
    );
    $this->assertObjectNotHasAttribute('_key', $results->first());
    $this->assertObjectNotHasAttribute('id', $results->first());
    $this->assertObjectHasAttribute('i', $results->first());
    $this->assertObjectHasAttribute('i2', $results->first());
    $this->assertCount(3, (array)$results->first());
});

test('get id conversion with wheres', function () {
    $query = DB::table('characters')
        ->where('id', 'NedStark');

    $results = $query->get();

    $this->assertSame(
        'FOR characterDoc IN characters FILTER characterDoc._key == @' . $query->aqb->getQueryId() . '_1',
        $query->toSql()
    );

    $this->assertObjectNotHasAttribute('_key', $results->first());
    $this->assertObjectHasAttribute('id', $results->first());
    expect($results->first()->id)->toBe('NedStark');
    expect($results)->toHaveCount(1);
});

test('model has correct ids', function () {
    $results = Character::all();

    expect($results->first()->id)->toBe('NedStark');
    expect($results->first()->_id)->toBe('characters/NedStark');
    expect($results->first()->_key)->toBeNull();
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
}
