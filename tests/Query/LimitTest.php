<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

uses(
    \Tests\TestCase::class,
);

test('limit', function () {
    $allCharacters = DB::table('characters')->get();
    $result = DB::table('characters')->limit(15)->get();

    expect($allCharacters->count())->toEqual(43);
    expect($result->count())->toEqual(15);
    expect($result->first())->toEqual($allCharacters->first());
});

test('limit with offset', function () {
    $allCharacters = DB::table('characters')->get();
    $result = DB::table('characters')->limit(15)->offset(5)->get();

    expect($allCharacters->count())->toEqual(43);
    expect($result->count())->toEqual(15);
    expect($result->first())->toEqual($allCharacters[5]);
});

test('take', function () {
    $allCharacters = DB::table('characters')->get();
    $result = DB::table('characters')->take(15)->get();

    expect($allCharacters->count())->toEqual(43);
    expect($result->count())->toEqual(15);
    expect($result->first())->toEqual($allCharacters->first());
});

test('skip & take', function () {
    $allCharacters = DB::table('characters')->get();
    $result = DB::table('characters')->skip(5)->take(15)->get();

    expect($allCharacters->count())->toEqual(43);
    expect($result->count())->toEqual(15);
    expect($result->first())->toEqual($allCharacters[5]);
});
