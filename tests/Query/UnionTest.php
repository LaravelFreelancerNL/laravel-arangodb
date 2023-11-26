<?php

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(
    TestCase::class,
);

test('union', function () {
    $charactersWithoutAge = DB::table('characters')
        ->whereNull('age');

    $query = DB::table('characters')
        ->where('surname', 'Targaryen')
        ->union($charactersWithoutAge);
    $targaryensAndCharactersWithoutAge = $query->get();

    expect($targaryensAndCharactersWithoutAge->count())->toBe(29);
});

test('unionAll', function () {
    $charactersWithoutAge = DB::table('characters')
        ->whereNull('age');

    $query = DB::table('characters')
        ->where('surname', 'Targaryen')
        ->unionAll($charactersWithoutAge);
    $union = $query->get();

    expect($union->count())->toBe(30);
});

test('multiple union', function () {
    $charactersWithoutAge = DB::table('characters')
        ->whereNull('age');

    $adults = DB::table('characters')
        ->where('age', '>=', 18);

    $query = DB::table('characters')
        ->where('surname', 'Targaryen')
        ->union($charactersWithoutAge)
        ->unionAll($adults);
    $union = $query->get();

    expect($union->count())->toBe(37);
});


test('union order')->todo();
test('union aggregates')->todo();
test('union groupBy')->todo();
