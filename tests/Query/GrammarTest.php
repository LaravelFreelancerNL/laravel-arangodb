<?php

use Tests\TestCase;

uses(
    TestCase::class,
);

test('wrap bypass', function () {
    $builder = getBuilder();
    $builder = $builder->select('*')
        ->from('users')
        ->where('i`d', '=', "a123");

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`i``d` == @'
        . $builder->getQueryId()
        . '_where_1 RETURN userDoc',
        $builder->toSql()
    );
});
