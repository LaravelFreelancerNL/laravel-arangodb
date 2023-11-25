<?php

declare(strict_types=1);

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;

uses(
    \Tests\TestCase::class,
    DatabaseTransactions::class
);

test('set variable', function () {
    $builder = DB::table('y')
        ->set('y', [1,2,3,4,5]);

    $this->assertSame(
        'LET y = @' . $builder->getQueryId() . '_variable_1 FOR yDoc IN y',
        $builder->toSql()
    );

    $results = $builder->get();

    expect($results->count())->toEqual(5);
});

test('set expression', function () {
    $builder = DB::table('y')
        ->set('y', new Expression('1..10'));

    $this->assertSame(
        'LET y = 1..10 FOR yDoc IN y',
        $builder->toSql()
    );

    $results = $builder->get();

    expect($results->count())->toEqual(10);
});
