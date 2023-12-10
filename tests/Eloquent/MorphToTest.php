<?php

use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;

uses(
    DatabaseTransactions::class
);

test('retrieve relation', function () {
    $location = Location::find('winterfell');
    $character = $location->capturable;

    expect($location->id)->toEqual('winterfell');
    expect($character->id)->toEqual($location->capturable_id);
    expect($character)->toBeInstanceOf(Character::class);
});

test('associate', function () {
    $location = Location::find('winterfell');
    $character = Character::find('TheonGreyjoy');

    $location->capturable()->associate($character);
    $location->save();
    $location = Location::find('winterfell');

    expect($location->capturable_id)->toEqual($character->id);
    expect($location->capturable)->toBeInstanceOf(Character::class);
});

test('with', function () {
    $location = Location::with('capturable')->find('winterfell');

    expect($location->capturable)->toBeInstanceOf(Character::class);
    expect($location->capturable->id)->toEqual('TheonGreyjoy');
});
