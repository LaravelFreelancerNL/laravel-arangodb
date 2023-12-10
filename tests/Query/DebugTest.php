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
    //    $query = DB::table('characters')
    //        ->where('name', 'Gilly')
    //        ->toRawSql();

    // TODO: apparently the connection isn't set for grammar generating an error when trying to escape values.
})->todo();

test('dumpRawSql', function () {
    //    $query = DB::table('characters')
    //        ->where('name', 'Gilly')
    //        ->dumpRawSql();

    // Same problem as toRawSql
})->todo();


test('dump', function () {
    $query = DB::table('characters')
        ->where('name', 'Gilly')
        ->dump();

    //TODO: apparently the connection isn't set for grammar generating an error when trying to escape values.
})->throwsNoExceptions();
