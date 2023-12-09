<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

uses(
    \Tests\TestCase::class,
);

test('count', function () {
    $results = DB::table('characters')->count();
    expect($results)->toEqual(43);
});

test('max', function () {
    $allCharacters = DB::table('characters')->whereNotNull('age')->orderBy('age', 'desc')->get();
    $results = DB::table('characters')->max('age');

    expect($results)->toEqual($allCharacters->first()->age);
});

test('min', function () {
    $allCharacters = DB::table('characters')->whereNotNull('age')->orderBy('age')->get();
    $results = DB::table('characters')->min('age');

    expect($results)->toEqual($allCharacters->first()->age);
});

test('avg', function () {
    $average = DB::table('characters')->avg('age');

    expect($average)->toEqual(25.6);
});


test('sum', function () {
    $average = DB::table('characters')->sum('age');

    expect($average)->toEqual(384);
});

test('exists is true', function () {
    $result = DB::table('characters')->where('id', 'NedStark')->exists();
    expect($result)->toBeTrue();
});

test('exists is false', function () {
    $result = DB::table('characters')->where('id', 'LyannaStark')->exists();
    expect($result)->toBeFalse();
});

test('doesntExist is true', function () {
    $result = DB::table('characters')->where('id', 'LyannaStark')->doesntExist();
    expect($result)->toBeTrue();
});

test('doesntExist is false', function () {
    $result = DB::table('characters')->where('id', 'NedStark')->doesntExist();
    expect($result)->toBeFalse();
});
