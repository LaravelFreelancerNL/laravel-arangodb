<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('update', function () {
    $characterData = [
        "id" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "tags" => [],
        "residence_id" => "winterfell"
    ];
    DB::table('characters')->insert($characterData);

    $results = DB::table('characters')->get();

    expect($results->last()->tags)->toBeArray();
    expect($results->last()->tags)->toBeEmpty();
});


test('update method', function () {
    $builder = getBuilder();
    $builder->getConnection()->shouldReceive('update')->once()->with(FluentAQL::class)->andReturn(1);
    $result = $builder->from('users')->where('userDoc._id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
    expect($result)->toEqual(1);
});
