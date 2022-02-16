<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('join', function () {
    $characters = DB::table('characters')
        ->join('locations', 'characters.residence_id', '=', 'locations.id')
        ->where('residence_id', '=', 'winterfell')
        ->get();

    expect($characters)->toHaveCount(15);
    expect($characters[0]->id)->toEqual('NedStark');
});

test('cross join', function () {
    $characters = DB::table('characters')
        ->crossJoin('locations')
        ->get();

    expect($characters)->toHaveCount(387);
});

test('left join', function () {
    $characters = DB::table('characters')
        ->leftJoin('locations', 'characters.residence_id', '=', 'locations.id')
        ->get();

    $charactersWithoutResidence = DB::table('characters')
        ->whereNull('residence_id')
        ->get();

    expect($characters)->toHaveCount(33);
    expect($characters[0]->id)->toEqual('NedStark');
    expect($charactersWithoutResidence)->toHaveCount(10);
});
