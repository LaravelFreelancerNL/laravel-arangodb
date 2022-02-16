<?php

use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('has', function () {
    $characters = Character::has('leads')->get();
    expect(count($characters))->toEqual(3);
});

test('has with minimum relation count', function () {
    $characters = Character::has('leads', '>=', 3)->get();
    expect(count($characters))->toEqual(1);
});

test('has morph', function () {
    $characters = Character::has('tags')->get();

    expect(count($characters))->toEqual(2);
});

test('doesnt have', function () {
    $characters = Character::doesntHave('leads')->get();
    expect(count($characters))->toEqual(40);
});

test('with count', function () {
    $characters = Character::withCount('leads')
        ->where('leads_count', '>', 0)
        ->get();

    expect(count($characters))->toEqual(3);
});
