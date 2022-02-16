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
    $location = Location::find('king-s-landing');

    $character = $location->character;

    expect($location->id)->toEqual('king-s-landing');
    expect($location->id)->toEqual($character->location_id);
    expect($character)->toBeInstanceOf(Character::class);
});

test('alternative relationship name and key', function () {
    $character = Character::find('SansaStark');
    $location = $character->leads;

    expect($location->id)->toEqual('winterfell');
    expect($location->id)->toEqual($character->location_id);
    expect($location)->toBeInstanceOf(Location::class);
});

test('create', function ($character) {
    $location = Location::find('riverrun');

    $location->leader()->create($character);
    $character = Character::find('EdmureTully');
    $location->leader()->associate($character);

    $location->push();

    $location->fresh();

    expect($location->leader->id)->toEqual('EdmureTully');
    expect($location->led_by)->toEqual('EdmureTully');
    expect($location->leader)->toBeInstanceOf(Character::class);
})->with('character');

test('save', function ($character) {
    $character = Character::create($character);
    $location = Location::find('riverrun');

    $location->character()->save($character);

    $character = $location->character;

    expect($character->id)->toEqual('EdmureTully');
    expect($location->id)->toEqual($character->location_id);
    expect($character)->toBeInstanceOf(Character::class);
})->with('character');

test('with', function () {
    $character = Character::with('leads')->find('SansaStark');

    expect($character->leads)->toBeInstanceOf(Location::class);
    expect($character->leads->id)->toEqual('winterfell');
});
