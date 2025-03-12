<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use TestSetup\Models\Character;
use TestSetup\Models\Location;

uses(
    DatabaseTransactions::class,
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

test('has', function () {
    $characters = Character::has('leads')->get();
    expect(count($characters))->toEqual(3);
});

test('has, with minimum relation count', function () {
    $characters = Character::has('leads', '>=', 3)->get();
    expect(count($characters))->toEqual(1);
});

test('orHas', function () {
    $characters = Character::has('leads')
        ->orHas('captured')
        ->get();

    expect(count($characters))->toEqual(4);
});

test('doesntHave', function () {
    $characters = Character::doesntHave('leads')->get();

    expect(count($characters))->toEqual(40);
});

test('orDoesntHave', function () {
    $characters = Character::where(function (Builder $query) {
        $query->doesntHave('leads')
            ->orDoesntHave('conquered');
    })
        ->get();

    $daenarys = $characters->first(function (Character $character) {
        return $character->id === 'DaenerysTargaryen';
    });

    expect(count($characters))->toEqual(42);
    expect($daenarys)->toBeNull();
});

test('whereDoesntHave', function () {
    $characters = Character::whereDoesntHave('leads', function (Builder $query) {
        $query->where('name', 'Astapor');
    })->get();

    $daenarys = $characters->first(function (Character $character) {
        return $character->id === 'DaenerysTargaryen';
    });
    expect($characters->count())->toBe(42);
    expect($daenarys)->toBeNull();
});

test('with', function () {
    $character = Character::with('leads')->find('SansaStark');

    expect($character->leads)->toBeInstanceOf(Location::class);
    expect($character->leads->id)->toEqual('winterfell');
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
