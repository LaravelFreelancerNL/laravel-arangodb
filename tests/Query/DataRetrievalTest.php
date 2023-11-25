<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class,
);

test('find', function () {
    $result = \DB::table('characters')->find('NedStark');

    expect($result->id)->toBe('NedStark');
});

test('first', function () {
    $query = \DB::table('characters')->where('id', '=', 'NedStark');
    $result = $query->first();

    expect($result->id)->toBe('NedStark');
});

test('get', function () {
    $result = \DB::table('characters')->where('id', '=', 'NedStark')->get();

    expect($result->count())->toBe(1);
    expect($result->first()->id)->toBe('NedStark');
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

test('simplePaginate', function () {
    $result = DB::table('characters')->simplePaginate(15)->toArray();

    expect(count($result['data']))->toEqual(15);
});

test('cursorPaginate', function () {
    $result = DB::table('characters')->orderBy('id')->cursorPaginate(15)->toArray();

    expect(count($result['data']))->toEqual(15);
});

test('pluck', function () {
    $results = DB::table('characters')->pluck('name', 'id');

    expect($results->count())->toEqual(43);
    expect($results['NedStark'])->toEqual('Ned');
});

