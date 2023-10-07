<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;

uses(
    \Tests\TestCase::class,
    DatabaseTransactions::class
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

test('paginate', function () {
    $result = DB::table('characters')->paginate(15)->toArray();
    expect($result['total'])->toEqual(43);
    expect(count($result['data']))->toEqual(15);
});

test('paginate with filters', function () {
    $result = DB::table('characters')
        ->where('residence_id', 'winterfell')
        ->paginate(5)
        ->toArray();
    expect($result['total'])->toEqual(15);
    expect(count($result['data']))->toEqual(5);
});

test('paginate with optional filters', function () {
    $residenceId = 'winterfell';
    $result = DB::table('characters')
        ->when(
            $residenceId,
            function ($query) use ($residenceId) {
                return $query->where('residence_id', '==', $residenceId)
                    ->orWhere('residence_id', '==', $residenceId);
            }
        )
        ->paginate(5)
        ->toArray();

    expect($result['total'])->toEqual(15);
    expect(count($result['data']))->toEqual(5);
});
