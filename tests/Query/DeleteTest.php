<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;

uses(
    DatabaseTransactions::class
);

test('basic delete', function () {
    $characters = DB::table('characters')->get();

    $affectedDocuments = DB::table('characters')->delete();

    $charactersAfterDelete = DB::table('characters')->get();

    expect($characters->count())->toBeGreaterThan(0);
    expect($affectedDocuments)->toEqual($characters->count());
    expect($charactersAfterDelete->count())->toEqual(0);
});

test('delete specific id', function () {
    $characters = DB::table('characters')->get();
    $firstCharacter = $characters->first();

    $affectedDocuments = DB::table('characters')->delete($firstCharacter->id);

    $charactersAfterDelete = DB::table('characters')->get();

    expect($characters->count())->toBeGreaterThan(0);
    expect($affectedDocuments)->toEqual(1);
    expect($charactersAfterDelete->count())->toEqual($characters->count() - 1);
});

test('delete selection', function () {
    $characters = DB::table('characters')->get();
    $selection = DB::table('characters')->where('age', '>', 40)->get();

    $affectedDocuments = DB::table('characters')->where('age', '>', 40)->delete();

    $charactersAfterDelete = DB::table('characters')->get();

    expect($characters->count())->toBeGreaterThan(0);
    expect($selection->count())->toBeGreaterThan(0);
    expect($selection->count())->toBeLessThan($characters->count());
    expect($affectedDocuments)->toEqual($selection->count());
    expect($charactersAfterDelete->count())->toEqual($characters->count() - $affectedDocuments);
});

test('truncate', function () {
    $characters = DB::table('characters')->get();

    DB::table('characters')->truncate();

    $charactersAfterDelete = DB::table('characters')->get();

    expect($characters->count())->toBeGreaterThan(0);
    expect($charactersAfterDelete->count())->toEqual(0);
});
