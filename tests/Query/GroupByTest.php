<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('group by', function () {
    $surnames = DB::table('characters')
        ->select('surname')
        ->groupBy('surname')
        ->get();

    expect($surnames)->toHaveCount(21);
    expect($surnames[1])->toEqual('Baelish');
});

test('group by multiple', function () {
    $groups = DB::table('characters')
        ->select('surname', 'residence_id')
        ->groupBy('surname', 'residence_id')
        ->get();

    expect($groups)->toHaveCount(29);
    expect($groups[4][0])->toEqual(null);
    expect($groups[4][1])->toEqual('the-red-keep');
    expect($groups[5][0])->toEqual('Baelish');
    expect($groups[5][1])->toEqual('the-red-keep');
});

test('having', function () {
    $surnames = DB::table('characters')
        ->select('surname')
        ->groupBy('surname')
        ->having('surname', 'LIKE', '%S%')
        ->get();

    expect($surnames)->toHaveCount(4);
    expect($surnames[1])->toEqual('Seaworth');
});

test('or having', function () {
    $ages = DB::table('characters')
        ->select('age')
        ->groupBy('age')
        ->having('age', '<', 20)
        ->orHaving('age', '>', 40)
        ->get();

    expect($ages)->toHaveCount(9);
    expect($ages[1])->toEqual(10);
});

test('having between', function () {
    $ages = DB::table('characters')
        ->select('age')
        ->groupBy('age')
        ->havingBetween('age', [20, 40])
        ->get();

    expect($ages)->toHaveCount(3);
    expect($ages[1])->toEqual(36);
});
