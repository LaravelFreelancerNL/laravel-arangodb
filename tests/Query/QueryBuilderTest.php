<?php

use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('toAql', function () {
    $query = \DB::table('characters')->where('id', '=', 'NedStark');

    $aql = $query->toAql();
    $result = $query->first();

    expect($aql)->toBe(
        'FOR characterDoc IN characters FILTER `characterDoc`.`_key` == @'
        . $query->getQueryId() . '_where_1'
    )
        ->and($result->id)->toBe('NedStark');
});
