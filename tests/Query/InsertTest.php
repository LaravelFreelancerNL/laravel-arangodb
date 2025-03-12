<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('insert', function () {
    $characterData = [
        "id" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "tags" => [],
        "residence_id" => "winterfell",
    ];
    DB::table('characters')->insert($characterData);

    $results = DB::table('characters')->get();

    expect($results->last()->tags)->toBeArray();
    expect($results->last()->tags)->toBeEmpty();
});


test('insert get id', function () {
    $builder = getBuilder($this->connection);

    $builder->getConnection()->shouldReceive('execute')->once()->andReturn(1);
    $result = $builder->from('users')->insertGetId(['email' => 'foo']);
    expect($result)->toEqual(1);
});

test('insertOrIgnore inserts data', function () {
    $characterData = [
        "_key" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "residence_id" => "winterfell",
    ];

    $result = DB::table('characters')
        ->where("name", "==", "Lyanna")
        ->get();

    expect($result->count())->toBe(0);


    DB::table('characters')->insertOrIgnore($characterData);

    $result = DB::table('characters')
        ->where("name", "=", "Lyanna")
        ->get();

    expect($result->count())->toBe(1);
});

test('insertOrIgnore doesnt error on duplicates', function () {
    $characterData = [
        "_key" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "residence_id" => "winterfell",
    ];
    DB::table('characters')->insert($characterData);

    DB::table('characters')->insertOrIgnore($characterData);

    $result = DB::table('characters')
        ->where("name", "=", "Lyanna")
        ->get();

    expect($result->count())->toBe(1);
});

test('insertOrIgnore with unique index on non-primary fields', function () {
    $userData = [
        "_key" => "LyannaStark",
        "username" => "Lyanna Stark",
        "email" => "l.stark@windsofwinter.com",
    ];
    DB::table('users')->insertOrIgnore($userData);

    $result = DB::table('users')
        ->first();

    expect($result->_id)->toBe('users/LyannaStark');

    $userData = [
        "username" => "Lya Stark",
        "email" => "l.stark@windsofwinter.com",
    ];
    DB::table('users')->insertOrIgnore($userData);

    $result = DB::table('users')
        ->first();

    expect($result->_id)->toBe('users/LyannaStark');
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
        ->where("name", "=", "Lyanna")
        ->get();

    expect($result->first()->tags)->toBeArray();
    expect($result->first()->tags)->toBeEmpty();
});

test('insertUsing', function () {
    // Let's give Baelish a user, what could possibly go wrong?
    $baelishes = DB::table('characters')
        ->where('surname', 'Baelish');

    DB::table('users')->insertUsing(['name', 'surname'], $baelishes);

    $user = DB::table('users')->where("surname", "=", "Baelish")->first();

    expect($user->surname)->toBe('Baelish');
});

test('insertOrIgnoreUsing', function () {
    // Let's give Baelish a user, what could possibly go wrong? Everyone trusts him...
    $baelishes = DB::table('characters')
        ->where('surname', 'Baelish');

    DB::table('users')->insertOrIgnoreUsing(['name', 'surname'], $baelishes);

    $user = DB::table('users')->where("surname", "=", "Baelish")->first();

    expect($user->surname)->toBe('Baelish');
});

test("insertOrIgnoreUsing doesn't error on duplicates", function () {
    // Let's give Baelish a user, what could possibly go wrong? Everyone trusts him...
    $baelish = DB::table('characters')
        ->where('surname', 'Baelish');

    DB::table('users')->insertUsing(['name', 'surname'], $baelish);

    // Let's do it again.
    DB::table('users')->insertOrIgnoreUsing(['name', 'surname'], $baelish);

    $user = DB::table('users')->where("surname", "=", "Baelish")->first();

    expect($user->surname)->toBe('Baelish');
});
