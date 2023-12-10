<?php

use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;

uses(
    DatabaseTransactions::class
);

test('retrieve relation', function () {
    $character = Character::find('TheonGreyjoy');
    $location = $character->conquered;

    expect($location->id)->toEqual('winterfell');
    expect($character->id)->toEqual($location->capturable_id);
    expect($character)->toBeInstanceOf(Character::class);
});

test('save', function () {
    $location = Location::find('winterfell');
    $character = Character::find('RamsayBolton');

    $character->conquered()->save($location);
    $location = Location::find('winterfell');

    expect($location->capturable_id)->toEqual($character->id);
    expect($character->conquered)->toBeInstanceOf(Location::class);
});

test('with', function () {
    $character = Character::with('conquered')->find('TheonGreyjoy');

    expect($character->conquered)->toBeInstanceOf(Location::class);
    expect($character->conquered->id)->toEqual('winterfell');
});
