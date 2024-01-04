<?php

use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;

uses(
    DatabaseTransactions::class
);

test('has', function () {
    $characters = Character::has('leads')->get();
    expect(count($characters))->toEqual(3);
});

test('has, with minimum relation count', function () {
    $characters = Character::has('leads', '>=', 3)->get();
    expect(count($characters))->toEqual(1);
});

test('doesntHave', function () {
    $characters = Character::doesntHave('leads')->get();
    expect(count($characters))->toEqual(40);
});

test('has on morphed relation', function () {
    $characters = Character::has('tags')->get();

    expect(count($characters))->toEqual(2);
});

test('withCount', function () {
    $characters = Character::withCount('leads')
        ->where('leads_count', '>', 0)
        ->get();

    expect(count($characters))->toEqual(3);
});

test('withExists', function () {
    $characters = Character::withExists('leads')
        ->get();

    expect(count($characters))->toEqual(43);
    expect($characters->where('leads_exists', true)->count())->toEqual(3);
});
