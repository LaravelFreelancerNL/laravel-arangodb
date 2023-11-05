<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('cross join', function () {
    $characters = DB::table('characters')
        ->crossJoin('locations')
        ->get();

    expect($characters)->toHaveCount(387);
});

test('join', function () {
    $query = DB::table('characters')
        ->join('locations', 'characters.residence_id', '=', 'locations.id')
        ->where('residence_id', '=', 'winterfell');

    $characters = $query->get();

    expect($characters)->toHaveCount(15);
    expect($characters[0]->id)->toEqual('NedStark');
});

test('left join', function () {
    $characters = DB::table('characters')
        ->leftJoin('locations', 'characterDoc.residence_id', '=', 'locationDoc._key')
        ->get();

    $charactersWithoutResidence = DB::table('characters')
        ->whereNull('residence_id')
        ->get();

    expect($characters)->toHaveCount(33);
    expect($characters[0]->id)->toEqual('NedStark');
    expect($charactersWithoutResidence)->toHaveCount(10);
});
