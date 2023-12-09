<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Query\JoinClause;
use Tests\TestCase;

uses(
    TestCase::class,
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

test('joinSub', function () {
    $locations = DB::table('locations')
        ->select()
        ->whereColumn('led_by', '=', 'characters.id');

    $builder = DB::table('characters')
        ->joinSub($locations, 'leads_locations', function (JoinClause $join) {
            $join->on('characters.id', '=', 'leads_locations.led_by');
        });

    $characters = $builder->get();

    expect($characters)->toHaveCount(7);
    expect($characters[0]->id)->toEqual('DaenerysTargaryen');
});


test('left join', function () {
    $query = DB::table('characters')
        ->leftJoin('locations', 'characterDoc.residence_id', '=', 'locationDoc._key');
    $characters = $query->get();

    ray('leftJoin', $query, $query->toSql());

    $charactersWithoutResidence = DB::table('characters')
        ->whereNull('residence_id')
        ->get();

    expect($characters)->toHaveCount(43);
    expect($characters[0]->id)->toEqual('NedStark');
    expect($charactersWithoutResidence)->toHaveCount(10);
});

test('leftJoinSub', function () {
    $locations = DB::table('locations')
        ->where('name', '=', "King's Landing");

    $query = DB::table('characters')
        ->select('characters', 'leads_locations')
        ->where('surname', 'Lannister')
        ->leftJoinSub($locations, 'leads_locations', function (JoinClause $join) {
            $join->on('characters.id', '=', 'leads_locations.led_by');
            // needs to be set on the join query
        });


    $characters = ($query->get())->toArray();

    expect($characters)->toHaveCount(4);
    expect(($characters[1])->id)->toEqual('CerseiLannister');
    expect(($characters[1])->location)->toEqual([
        42.639752,
        18.110189
    ]);
})->todo();
