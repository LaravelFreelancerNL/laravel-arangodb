<?php

use Illuminate\Support\Facades\DB;

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

test('union order', function () {
    $charactersWithoutAge = DB::table('characters')
        ->whereNull('age');

    $query = DB::table('characters')
        ->where('surname', 'Targaryen')
        ->union($charactersWithoutAge)
        ->orderBy('surname')
        ->orderBy('name');

    $results = $query->get();

    expect($results->count())->toBe(29);
    expect(($results->first())->name)->toBe('Bronn');
    expect(($results->last())->name)->toBe('Margaery');
});

test('union limit offset', function () {
    $charactersWithoutAge = DB::table('characters')
        ->whereNull('age');

    $query = DB::table('characters')
        ->where('surname', 'Targaryen')
        ->union($charactersWithoutAge)
        ->orderBy('surname')
        ->orderBy('name')
        ->limit(5)
        ->offset(10);

    $results = $query->get();

    expect($results->count())->toBe(5);
    expect(($results->first())->name)->toBe('Robert');
    expect(($results->last())->name)->toBe('Roose');
});


test('union aggregates')->todo();


test('union groupBy', function () {
    $charactersWithoutAge = DB::table('characters')
        ->whereNull('age');

    $query = DB::table('characters')
        ->where('surname', 'Targaryen')
        ->union($charactersWithoutAge)
        ->groupBy('surname');

    $results = $query->get();

    expect($results->count())->toBe(29);
    expect(($results->first())->name)->toBe('Ygritte');
    expect(($results->last())->name)->toBe('Margaery');
})->todo();
