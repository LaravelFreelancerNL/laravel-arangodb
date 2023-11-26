<?php

use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('when false', function () {
    $id = null;

    $query = \DB::table('characters')
        ->when($id, function (Builder $query, string $id) {
        $query->where('id', $id);
    });

    $aql = $query->toAql();
    $results = $query->get();

    expect($aql)->toBe(
        'FOR characterDoc IN characters RETURN characterDoc'
    )
        ->and($results->count())->toBe(43);
});

test('when true', function () {
    $id = 'NedStark';

    $query = \DB::table('characters')
        ->when($id, function (Builder $query, string $id) {
            $query->where('id', $id);
        });

    $aql = $query->toAql();
    $results = $query->get();

    expect($aql)->toBe(
        'FOR characterDoc IN characters FILTER `characterDoc`.`_key` == @'
        . $query->getQueryId() . '_where_1 RETURN characterDoc'
    )
        ->and($results->count())->toBe(1);
});
