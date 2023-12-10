<?php

declare(strict_types=1);

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Query\Enums\VariablePosition;

test('set variable', function () {
    $query = DB::table('y')
        ->set('y', [1,2,3,4,5]);

    $this->assertSame(
        'LET y = @' . $query->getQueryId() . '_preIterationVariables_1 FOR yDoc IN y RETURN yDoc',
        $query->toSql()
    );

    $results = $query->get();

    expect($results->count())->toEqual(5);
});

test('set expression', function () {
    $query = DB::table('y')
        ->set('y', new Expression('1..10'));

    $this->assertSame(
        'LET y = 1..10 FOR yDoc IN y RETURN yDoc',
        $query->toSql()
    );

    $results = $query->get();

    expect($results->count())->toEqual(10);
});

test('set subquery', function () {
    $subquery = DB::table('y')
        ->set('y', new Expression('1..10'));

    $query = DB::table('x')
        ->set('x', $subquery);

    $this->assertSame(
        'LET x = (LET y = 1..10 FOR yDoc IN y RETURN yDoc) FOR xDoc IN x RETURN xDoc',
        $query->toSql()
    );

    $results = $query->get();

    expect($results->count())->toEqual(10);
});


test('set post traversal variable ', function () {
    $query = DB::table('characters')
        ->select('characters.*', 'y as yColumn')
        ->set('y', [1,2,3,4,5], VariablePosition::postIterations);

    $this->assertSame(
        'FOR characterDoc IN characters LET y = @' . $query->getQueryId() . '_postIterationVariables_1 RETURN MERGE(characterDoc, {yColumn: `y`})',
        $query->toSql()
    );

    $results = $query->get();

    expect($results->count())->toEqual(43);
});
