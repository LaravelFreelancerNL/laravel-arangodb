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
    $location = Location::find('winterfell');

    $inhabitants = $location->inhabitants;
    expect($inhabitants)->toHaveCount(15);
    expect($location->id)->toEqual($inhabitants->first()->residence_id);
    expect($inhabitants->first())->toBeInstanceOf(Character::class);
});

test('save', function ($character) {
    $character = Character::create($character);
    $location = Location::find('riverrun');

    $location->inhabitants()->save($character);

    $location->fresh();

    $inhabitants = $location->inhabitants;

    expect($inhabitants)->toHaveCount(1);
    expect($inhabitants->first()->id)->toEqual('EdmureTully');
    expect($location->id)->toEqual($inhabitants->first()->residence_id);
    expect($inhabitants->first())->toBeInstanceOf(Character::class);
})->with('character');

test('create', function ($character) {
    $location = Location::find('riverrun');

    $location->inhabitants()->create($character);
    $character = Character::find('EdmureTully');
    $location->fresh();

    expect($location->inhabitants->first()->id)->toEqual('EdmureTully');
    expect($character->residence_id)->toEqual('riverrun');
    expect($location->inhabitants->first())->toBeInstanceOf(Character::class);
})->with('character');

test('first or create', function ($character) {
    $location = Location::find('riverrun');

    $location->inhabitants()->firstOrCreate($character);
    $character = Character::find('EdmureTully');
    $location->fresh();

    expect($location->inhabitants->first()->id)->toEqual('EdmureTully');
    expect($character->residence_id)->toEqual('riverrun');
    expect($location->inhabitants->first())->toBeInstanceOf(Character::class);
})->with('character');

test('first or new', function ($character) {
    $location = Location::find('riverrun');

    $character = $location->inhabitants()->firstOrNew($character);

    expect($character)->toBeInstanceOf(Character::class);
    expect($character->id)->toEqual($character['id']);
    expect($character->residence_id)->toEqual('riverrun');
})->with('character');

test('with', function () {
    $location = Location::with('inhabitants')->find('winterfell');

    expect($location->inhabitants->first())->toBeInstanceOf(Character::class);
    expect($location->inhabitants->first()->id)->toEqual('NedStark');
});
