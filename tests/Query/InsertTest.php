<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('insert', function () {
    $characterData = [
        "id" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "tags" => [],
        "residence_id" => "winterfell"
    ];
    DB::table('characters')->insert($characterData);

    $result = DB::table('characters')->first();

    expect($result->tags)->toBeArray();
    expect($result->tags)->toBeEmpty();
});


test('insert get id', function () {
    $builder = getBuilder();

    $builder->getConnection()->shouldReceive('execute')->once()->andReturn(1);
    $result = $builder->from('users')->insertGetId(['email' => 'foo']);
    expect($result)->toEqual(1);
});

test('insert or ignore inserts data', function () {
    $characterData = [
        "_key" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "residence_id" => "winterfell"
    ];

    DB::table('characters')->insertOrIgnore($characterData);

    $result = DB::table('characters')
        ->where("name", "==", "Lyanna")
        ->get();

    expect($result->count())->toBe(1);
});

test('insert or ignore doesnt error on duplicates', function () {
    $characterData = [
        "_key" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "residence_id" => "winterfell"
    ];
    DB::table('characters')->insert($characterData);

    DB::table('characters')->insertOrIgnore($characterData);

    $result = DB::table('characters')
        ->where("name", "==", "Lyanna")
        ->get();

    expect($result->count())->toBe(1);
});

test('insert embedded empty array', function () {
    $characterData = [
        "_key" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "residence_id" => "winterfell",
        "tags" => [],
    ];
    DB::table('characters')->insert($characterData);

    DB::table('characters')->insertOrIgnore($characterData);

    $result = DB::table('characters')
        ->where("name", "==", "Lyanna")
        ->get();

    expect($result->first()->tags)->toBeArray();
    expect($result->first()->tags)->toBeEmpty();
});
