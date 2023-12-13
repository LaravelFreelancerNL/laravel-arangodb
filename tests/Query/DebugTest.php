<?php

use Illuminate\Support\Facades\DB;

test('toAql', function () {
    $query = DB::table('characters')->where('id', '=', 'NedStark');

    $aql = $query->toAql();
    $result = $query->first();

    expect($aql)->toBe(
        'FOR characterDoc IN characters FILTER `characterDoc`.`_key` == @'
        . $query->getQueryId() . '_where_1 RETURN characterDoc'
    )
        ->and($result->id)->toBe('NedStark');
});

test('toSql', function () {
    $query = DB::table('characters')->where('id', '=', 'NedStark');

    $aql = $query->toSql();
    $result = $query->first();

    expect($aql)->toBe(
        'FOR characterDoc IN characters FILTER `characterDoc`.`_key` == @'
        . $query->getQueryId() . '_where_1 RETURN characterDoc'
    )
        ->and($result->id)->toBe('NedStark');
});


test('toRawSql', function () {
    $query = DB::table('characters')
        ->where('name', 'Gilly');

    $aql = $query->toRawSql();

    expect($aql)->toBe(
        'FOR characterDoc IN characters FILTER `characterDoc`.`name` == "Gilly" RETURN characterDoc'
    );
});

test('toRawSql with single quote', function () {
    $query = DB::table('characters')
        ->where('name', "H'ghar");

    $aql = $query->toRawSql();

    expect($aql)->toBe(
        'FOR characterDoc IN characters FILTER `characterDoc`.`name` == "'."H\'ghar".'" RETURN characterDoc'
    );
});


test('toRawSql with multiple binds', function () {
    $names = [
        "Ned",
        "Robert",
        "Jaime",
        "Catelyn",
        "Cersei",
        "Daenerys",
        "Jorah",
        "Petyr",
        "Viserys",
    ];

    $query = DB::table('characters');
    $query->where('name', 'Gilly');

    for($i = 0; $i < 9; $i++) {
        $query->orWhere('name', $names[$i]);
    }

    $aql = $query->toRawSql();

    $rawAql = 'FOR characterDoc IN characters FILTER `characterDoc`.`name` == "Gilly"';
    for($i = 0; $i < 9; $i++) {
        $rawAql .= ' or `characterDoc`.`name` == "'.$names[$i].'"';
    }
    $rawAql .= ' RETURN characterDoc';

    expect($aql)->toBe(
         $rawAql
    );
});

test('dumpRawSql', function () {
        $query = DB::table('characters')
            ->where('name', 'Gilly')
            ->dumpRawSql();
})->throwsNoExceptions();


test('dump', function () {
    $query = DB::table('characters')
        ->where('name', 'Gilly')
        ->dump();
})->throwsNoExceptions();
