<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;

uses(
    \Tests\TestCase::class,
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

test('having raw', function () {
    $query = DB::table('characters')
        ->select('surname')
        ->groupBy('surname')
        ->havingRaw('`surname` LIKE "Lannister"', []);

    $surnames = $query->get();

    $this->assertSame(
        'FOR characterDoc IN characters COLLECT surname = `characterDoc`.`surname`'
        . ' FILTER `surname` LIKE "Lannister"'
        . ' RETURN `surname`',
        $query->toSql()
    );

    expect($surnames)->toHaveCount(1);
    expect($surnames[0])->toEqual('Lannister');
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

test('Nested having', function () {
    $surnames = DB::table('characters')
        ->select('surname')
        ->groupBy('surname')
        ->having(function ($query) {
            $query->having('surname', '=', 'Lannister')
                ->orHaving('surname', '==', 'Stark');
        })->get();

    expect($surnames)->toHaveCount(2);
    expect($surnames[0])->toEqual('Lannister');
    expect($surnames[1])->toEqual('Stark');
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


test('having null', function () {
    $names = DB::table('characters')
        ->select('name')
        ->groupBy('name', 'residence_id')
        ->havingNull('residence_id')
        ->get();

    expect($names)->toHaveCount(10);
    expect($names[1])->toEqual("Daario");
});

test('having not null', function () {
    $names = DB::table('characters')
        ->select('name')
        ->groupBy('name', 'residence_id')
        ->havingNotNull('residence_id')
        ->get();

    expect($names)->toHaveCount(33);
    expect($names[1])->toEqual("Bran");
});

test("keep selected columns after groupBy")->todo();
