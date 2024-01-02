<?php

test('wrap multiple columns', function () {
    $builder = getBuilder();
    $builder = $builder->select(['id', '_id', 'email'])
        ->from('users');

    $this->assertSame(
        'FOR userDoc IN users RETURN {id: `userDoc`.`_key`, _id: `userDoc`.`_id`, email: `userDoc`.`email`}',
        $builder->toSql()
    );
});

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
