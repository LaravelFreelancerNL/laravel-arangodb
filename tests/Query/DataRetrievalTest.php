<?php

use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Support\Collection;

uses(
    TestCase::class,
    DatabaseTransactions::class,
);

test('find', function () {
    $result = DB::table('characters')->find('NedStark');

    expect($result->id)->toBe('NedStark');
});

test('first', function () {
    $query = DB::table('characters')->where('id', '=', 'NedStark');
    $result = $query->first();

    expect($result->id)->toBe('NedStark');
});

test('get', function () {
    $result = DB::table('characters')->where('id', '=', 'NedStark')->get();

    expect($result->count())->toBe(1);
    expect($result->first()->id)->toBe('NedStark');
});

test('value', function () {
    $query = DB::table('characters')->where('id', '=', 'NedStark');
    $result = $query->value('name');

    expect($result)->toBe('Ned');
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

test('chunk', function () {
    $count = 0;
    DB::table('characters')->orderBy('id')->chunk(10, function (Collection $characters) use (&$count) {
        foreach ($characters as $character) {
            $count++;
        }
    });

    expect($count)->toBe(43);
});


test('chunk stop', function () {
    $count = 0;
    DB::table('characters')->orderBy('id')->chunk(10, function (Collection $characters) use (&$count) {
        foreach ($characters as $character) {
            $count++;
        }
        if($count > 20) {
            return false;
        }
        return true;
    });

    expect($count)->toBe(30);
});

test('chunkById', function () {
    $count = 0;
    DB::table('characters')->where('alive', true)->chunkById(10, function (Collection $characters) use (&$count) {
        foreach ($characters as $character) {
            $count++;
        }
    });

    expect($count)->toBe(27);
});

test('lazy', function () {
    $count = 0;
    DB::table('characters')->orderBy('id')->lazy()->each(function (object $character) use (&$count) {
        $count++;
    });

    expect($count)->toBe(43);
});

test('lazyById', function () {
    $count = 0;
    DB::table('characters')->lazyById()->each(function (object $character) use (&$count) {
        $count++;
    });

    expect($count)->toBe(43);
});

test('lazyByIdDesc', function () {
    $asc = [];
    $desc = [];
    DB::table('characters')->lazyById()->each(function (object $character) use (&$asc) {
        $asc[] = $character->id;
    });

    DB::table('characters')->lazyByIdDesc()->each(function (object $character) use (&$desc) {
        $desc[] = $character->id;
    });

    expect(count($asc))->toBe(43);
    expect(count($desc))->toBe(43);
    expect($asc[0])->toBe(end($desc));
    expect($desc[0])->toBe(end($asc));
});

test('sole', function () {
    $result = DB::table('characters')->where('name', 'Gilly')->sole();

    expect($result->name)->toBe('Gilly');
});

test('sole on more than one result', function () {
    $result = DB::table('characters')->sole();

    expect($result->name)->toBe('Gilly');
})->throws(MultipleRecordsFoundException::class);
