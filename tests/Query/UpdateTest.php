<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('update', function () {
    $characterData = [
        "_key" => "NedStark",
        "name" => "Ned",
        "surname" => "Stark",
        "alive" => false,
        "age" => 42,
        "residence_id" => "winterfell",
        "location_id" => "king-s-landing"
    ];
    DB::table('characters')->where('id', 'NedStark')->update($characterData);

    $result = DB::table('characters')->where('id', 'NedStark')->first();

    expect($result->age)->toBe(42)
        ->and($result->alive)->toBeFalse();
});

test('update with join', function () {
    $characterData = [
        "_key" => "NedStark",
        "name" => "Ned",
        "surname" => "Stark",
        "alive" => false,
        "age" => 42,
        "residence_id" => "winterfell",
        "location_id" => "king-s-landing"
    ];

    $updateResult = DB::table('characters')
        ->join('locations', 'characters.residence_id', '=', 'locations.id')
        ->where('locations.id', 'winterfell')
        ->where('id', 'NedStark')
        ->update($characterData);

    $result = DB::table('characters')->where('id', 'NedStark')->first();
    expect($updateResult)->toBe(1)
        ->and($result->age)->toBe(42)
        ->and($result->alive)->toBeFalse();
})->todo();

test('update with json syntax', function () {
    $words = 'Hear me Roar!';

    $data = [
        'en->words' => $words,
    ];

    $updateResult = DB::table('houses')
        ->where('id', 'lannister')
        ->update($data);

    $result = DB::table('houses')->where('id', 'lannister')->first();

    expect($updateResult)->toBe(1)
        ->and($result->en->words)->toBe($words);
});

test('updateOrInsert updates', function () {
    DB::table('characters')
        ->updateOrInsert(["_key" => "NedStark"], ["alive" => false, "age" => 42]);

    $result = DB::table('characters')->where('id', 'NedStark')->first();

    expect($result->age)->toBe(42)
        ->and($result->alive)->toBeFalse();
});

test('updateOrInsert inserts', function () {
    $result = DB::table('characters')->where('id', 'LyannaStark')->exists();
    expect($result)->toBeFalse();

    DB::table('characters')
        ->updateOrInsert(["_key" => "LyannaStark"], [
            "name" => "Lyanna",
            "surname" => "Stark",
            "alive" => false,
            "age" => 25,
            "residence_id" => "winterfell",
            "tags" => [],
        ]);

    $result = DB::table('characters')->where('id', 'LyannaStark')->exists();

    expect($result)->toBeTrue();
});


test('increment', function () {
    $youngNed = DB::table('characters')->where('id', 'NedStark')->first();

    DB::table('characters')->where('id', 'NedStark')->increment("age", 5);

    $oldNed = DB::table('characters')->where('id', 'NedStark')->first();

    expect($oldNed->age)->toBe($youngNed->age + 5);
});

test('increment with additional fields', function () {
    $youngNed = DB::table('characters')->where('id', 'NedStark')->first();

    DB::table('characters')->where('id', 'NedStark')->increment("age", 5, ["alive" => false]);

    $oldNed = DB::table('characters')->where('id', 'NedStark')->first();

    expect($oldNed->age)->toBe($youngNed->age + 5)
        ->and($oldNed->alive)->toBeFalse();
});

test('incrementEach', function () {
    $youngNed = DB::table('characters')->where('id', 'NedStark')->first();

    DB::table('characters')->where('id', 'NedStark')->incrementEach([
        'age' => 5,
        'number_of_children' => 4,
    ]);

    $oldNed = DB::table('characters')->where('id', 'NedStark')->first();

    expect($oldNed->age)->toBe($youngNed->age + 5);
    expect($oldNed->number_of_children)->toBe(4);
});

test('decrement', function () {
    $oldNed = DB::table('characters')->where('id', 'NedStark')->first();

    DB::table('characters')->where('id', 'NedStark')->decrement("age", 5);

    $youngerNed = DB::table('characters')->where('id', 'NedStark')->first();

    expect($youngerNed->age)->toBe($oldNed->age - 5);
});

test('decrementEach', function () {
    $oldNed = DB::table('characters')->where('id', 'NedStark')->first();

    DB::table('characters')->where('id', 'NedStark')->decrementEach([
        'age' => 5,
        'number_of_children' => 4,
    ]);
    ;

    $youngerNed = DB::table('characters')->where('id', 'NedStark')->first();

    expect($youngerNed->age)->toBe($oldNed->age - 5)
        ->and($youngerNed->number_of_children)->toBe(-4);
});

test('upsert inserts new document', function () {
    $characterData = [
        "id" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "residence_id" => "winterfell",
        "tags" => [],
    ];
    DB::table('characters')->upsert($characterData, ['id', 'name'], ['id']);

    $result = DB::table('characters')
        ->where("name", "=", "Lyanna")
        ->first();

    expect($result->id)->toBe($characterData['id']);
    expect($result->name)->toBe($characterData['name']);
});

test('upsert updates specific fields', function () {
    $characterData = [
        "_key" => "NedStark",
        "name" => "Ned",
        "surname" => "Stark",
        "alive" => false,
        "age" => 42,
        "residence_id" => "winterfell",
        "location_id" => "king-s-landing"
    ];
    $result = DB::table('characters')->upsert([$characterData], ['id'], ['alive', 'age']);

    $deadNed = DB::table('characters')->where('id', 'NedStark')->first();

    expect($result)->toBe(1);
    expect($deadNed->alive)->toBeFalse();
    expect($deadNed->age)->toBe(42);
});

test('upsert with json syntax', function () {
    $words = 'Hear me Roar!';

    $data = [
        'id' => 'lannister',
        'en->words' => $words,
    ];

    $upsertResult = DB::table('houses')->upsert([$data], ['id'], ['en->words']);

    $result = DB::table('houses')->where('id', 'lannister')->first();

    expect($upsertResult)->toBe(1)
        ->and($result->en->words)->toBe($words)
        ->and($result->en->description)->not->toBeEmpty();
});

test('upsert returns 0 when values is not set', function () {
    $upsertResult = DB::table('houses')->upsert([], ['id'], ['en->words']);
    expect($upsertResult)->toBe(0);
});

test('upsert inserts if update is not set', function () {
    $characterData = [
        "id" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "residence_id" => "winterfell",
        "tags" => [],
    ];

    DB::table('characters')->upsert($characterData, ['id', 'name']);

    $result = DB::table('characters')
        ->where("name", "=", "Lyanna")
        ->first();

    expect($result->id)->toBe($characterData['id']);
    expect($result->name)->toBe($characterData['name']);

});
