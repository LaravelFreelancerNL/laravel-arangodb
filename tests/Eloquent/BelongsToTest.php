<?php

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());
});

afterEach(function () {
    Carbon::setTestNow(null);
    Carbon::resetToStringFormat();
    Model::unsetEventDispatcher();
    M::close();
});

test('retrieve relation', function () {
    $parent = Character::find('NedStark');
    $children = $parent->children;

    expect($children[0])->toBeInstanceOf(Character::class);

    expect(true)->toBeTrue();
});

test('alternative relationship name and key', function () {
    $location = Location::find('winterfell');
    $character = $location->leader;

    expect($character->id)->toEqual('SansaStark');
    expect($character->id)->toEqual($location->led_by);
    expect($character)->toBeInstanceOf(Character::class);
});

test('associate', function () {
    $character = Character::find('TheonGreyjoy');

    $location = new Location(
        [
            'id' => 'pyke',
            'name' => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );

    $location->leader()->associate($character);
    $location->save();

    $character->fresh();

    $location = $character->leads;

    expect($location->id)->toEqual('pyke');
    expect($character->id)->toEqual($location->led_by);
    expect($location)->toBeInstanceOf(Location::class);

    $location->delete();
});

test('dissociate', function () {
    $character = Character::find('NedStark');
    expect('winterfell')->toEqual($character->residence_id);

    $character->residence()->dissociate();
    $character->save();

    $character->fresh();
    expect($character->residence_id)->toBeNull();
});

test('with', function () {
    $location = Location::with('leader')->find('winterfell');

    expect($location->leader)->toBeInstanceOf(Character::class);
    expect($location->leader->id)->toEqual('SansaStark');
});
