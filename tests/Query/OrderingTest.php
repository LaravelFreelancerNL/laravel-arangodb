<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('orderBy', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->orderBy('email')->orderBy('age', 'desc');
    $this->assertSame(
        'FOR userDoc IN users SORT `userDoc`.`email` ASC, `userDoc`.`age` DESC RETURN userDoc',
        $builder->toSql()
    );
});

test('orderByDesc', function () {
    $results = DB::table('characters')
        ->orderByDesc('age')
        ->toSql();

    expect($results)->toEqual('FOR characterDoc IN characters SORT `characterDoc`.`age` DESC RETURN characterDoc');
});

test('latest', function () {
    $results = DB::table('characters')
        ->latest()
        ->toSql();

    expect($results)->toEqual('FOR characterDoc IN characters SORT `characterDoc`.`created_at` DESC RETURN characterDoc');
});

test('oldest', function () {
    $results = DB::table('characters')
        ->oldest()
        ->toSql();

    expect($results)->toEqual('FOR characterDoc IN characters SORT `characterDoc`.`created_at` ASC RETURN characterDoc');
});

test('inRandomOrder', function () {
    $results = DB::table('characters')
        ->inRandomOrder()
        ->toSql();

    expect($results)->toEqual('FOR characterDoc IN characters SORT RAND() RETURN characterDoc');
});

test('orderByRaw', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->orderByRaw('userDoc.age @direction', ['@direction' => 'ASC']);
    $this->assertSame(
        'FOR userDoc IN users SORT userDoc.age @direction RETURN userDoc',
        $builder->toSql()
    );
});


test('reorder', function () {
    $builder = getBuilder();
    $builder->select('*')
        ->from('users')
        ->orderByRaw('userDoc.age @direction', ['@direction' => 'ASC'])
        ->reorder();

    $this->assertSame(
        'FOR userDoc IN users RETURN userDoc',
        $builder->toSql()
    );
});
