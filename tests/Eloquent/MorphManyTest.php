<?php

use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('retrieve relation', function () {
    $character = Character::find('DaenerysTargaryen');

    $locations = $character->captured;

    expect($locations->first())->toBeInstanceOf(Location::class);
    expect($locations)->toHaveCount(5);
    expect($character->id)->toEqual($locations->first()->capturable->id);
});

test('save', function () {
    $location = Location::find('winterfell');
    expect($location->capturable_id)->toEqual('TheonGreyjoy');

    $character = Character::find('RamsayBolton');
    $character->captured()->save($location);

    expect($location->capturable_id)->toEqual($character->id);
    expect($character->captured)->toHaveCount(1);
    expect($character->captured->first())->toBeInstanceOf(Location::class);
});

test('with', function () {
    $character = Character::with('captured')->find('TheonGreyjoy');

    expect($character->captured->first())->toBeInstanceOf(Location::class);
    expect($character->captured->first()->id)->toEqual('winterfell');
});
