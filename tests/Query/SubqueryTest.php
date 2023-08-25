<?php

use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('subquery where', function () {
    $characters = Character::where(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id')
            ->limit(1);
    }, 'Dragonstone')
        ->get();
    expect($characters[0]->id)->toEqual('DaenerysTargaryen');
});

test('where sub', function () {
    $characters = Character::where('id', '==', function ($query) {
        $query->select('led_by')
            ->from('locations')
            ->where('name', 'Dragonstone')
            ->limit(1);
    })
        ->get();

    expect($characters[0]->id)->toEqual('DaenerysTargaryen');
});

test('where exists with multiple results', function () {
    $characters = Character::whereExists(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id');
    })
        ->get();
    expect(count($characters))->toEqual(3);
});

test('where exists with limit', function () {
    $characters = Character::whereExists(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id')
            ->limit(1);
    })
        ->get();
    expect(count($characters))->toEqual(3);
});

test('where not exists with multiple results', function () {
    $characters = Character::whereNotExists(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id');
    })
        ->get();
    expect(count($characters))->toEqual(40);
});

test('where not exists with limit', function () {
    $characters = Character::whereNotExists(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id')
            ->limit(1);
    })
        ->get();
    expect(count($characters))->toEqual(40);
});
