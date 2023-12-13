<?php

use Illuminate\Support\Facades\DB;

test('basic select', function () {
    $results = DB::table('characters')->select()->get();

    expect($results)->toHaveCount(43);

    expect(count((array)$results[0]))->toBe(9);
});

test('basic select with specific column', function () {
    $results = DB::table('characters')->select('name')->first();

    expect($results->name)->toBe('Ned');
});

test('basic select with specific columns', function () {
    $results = DB::table('characters')->select(['name', 'surname', 'age'])->limit(1)->get();

    expect($results[0])->toHaveProperties(['name', 'surname', 'age']);
});

test('select nested data through alias', function () {
    $query = DB::table('houses')->select([
        'name',
        'en.description as description',
    ])->limit(1);
    $house = $query->first();

    expect((array)$house)->toHaveCount(2);
    expect($house)->toHaveProperty('name');
    expect($house)->toHaveProperty('description');
});

test('select nested data without alias', function () {
    $query = DB::table('houses')->select([
        'name',
        'en.description',
        'en.words',
    ]);

    $house = $query->first();

    expect((array)$house)->toHaveCount(2);
    expect($house)->toHaveProperty('name');
    expect($house)->toHaveProperty('en');
    expect($house->en)->toHaveProperty('description');
});

test('select nested data through string-key', function () {
    $house = DB::table('houses')->select([
        'name',
        'description' => 'en.description',
    ])->limit(1)->first();

    expect((array)$house)->toHaveCount(2);
    expect($house)->toHaveProperty('name');
    expect($house)->toHaveProperty('description');
});

test('select nested data with embedded objects though multiple paths', function () {
    $house = DB::table('houses')->select([
        'name',
        'en.description',
        'en.words',
    ])->limit(1)->first();

    expect((array)$house)->toHaveCount(2);
    expect($house)->toHaveProperty('name');
    expect($house)->toHaveProperty('en');
    expect((array)$house->en)->toHaveCount(2);
});

test('select nested data with multilevel embedded objects though multiple paths', function () {
    $house = DB::table('houses')->select([
        'en.summary.short',
        'en.summary.long',
        'en.words',
        'name',
    ])->limit(1)->first();

    expect((array)$house)->toHaveCount(2);
    expect($house)->toHaveProperty('name');
    expect($house)->toHaveProperty('en');
    expect((array)$house->en)->toHaveCount(2);
    expect((array)$house->en->summary)->toHaveCount(2);
});

test('select nested data with multilevel embedded objects and aliases', function () {
    $house = DB::table('houses')->select([
        'en.summary.short',
        'en.summary.long as long',
        'en.words',
        'name',
    ])->limit(1)->first();

    expect((array)$house)->toHaveCount(3);
    expect($house)->toHaveProperty('name');
    expect($house)->toHaveProperty('en');
    expect($house)->toHaveProperty('long');
    expect((array)$house->en)->toHaveCount(2);
    expect((array)$house->en->summary)->toHaveCount(1);
});

test('addSelect', function () {
    $house = DB::table('houses')
        ->select('en')
        ->addSelect('name')
        ->limit(1)
        ->first();

    expect((array)$house)->toHaveCount(2);
    expect($house)->toHaveProperty('name');
    expect($house)->toHaveProperty('en');
});

test('addSelect multiple', function () {
    $house = DB::table('houses')
        ->select('en.description')
        ->addSelect(['name', 'en.words'])
        ->limit(1)
        ->first();

    expect((array)$house)->toHaveCount(2);
    expect($house)->toHaveProperty('name');
    expect($house)->toHaveProperty('en');
    expect((array)$house->en)->toHaveCount(2);
});

test('addSelect with alias', function () {
    $house = DB::table('houses')->select('en.description')->addSelect(['house_name' => 'name', 'en.words'])->limit(1)->first();

    expect((array)$house)->toHaveCount(2);
    expect($house)->toHaveProperty('house_name');
    expect($house)->toHaveProperty('en');
    expect((array)$house->en)->toHaveCount(2);
});

test('select subquery', function () {
    //find people connected to the seat of house stark
    $locationQuery = DB::table('houses')
        ->select('location_id')
        ->where('name', 'Stark')
        ->limit(1);

    $query = DB::table('characters')
        ->select(['location' => $locationQuery, 'name'])
        ->where('residence_id', 'location');

    $results = $query->get();

    expect($results->count())->toBe(15);
    expect($results->first()->name)->toBe('Ned');
    expect($results->first()->location)->toBe('winterfell');
    expect($results->last()->name)->toBe('Margaery');
    expect($results->last()->location)->toBe('winterfell');
});
