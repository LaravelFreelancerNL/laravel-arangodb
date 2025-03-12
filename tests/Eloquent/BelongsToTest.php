<?php

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Mockery as M;
use TestSetup\Models\Character;
use TestSetup\Models\House;
use TestSetup\Models\Location;

uses(
    DatabaseTransactions::class,
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
        ],
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

test('with on single model', function () {
    $house = House::with('head')->find('lannister');

    expect($house->head)->toBeInstanceOf(Character::class);
    expect($house->head->id)->toEqual('TywinLannister');
});

test('with on multiple model', function () {
    $houses = House::with('head')->get();

    expect($houses->count())->toBe(3);
    expect($houses->first()->head)->toBeInstanceOf(Character::class);
    expect($houses->first()->head->id)->toEqual('TywinLannister');
});

test('load', function () {
    $house = House::find('lannister');
    $house->load('head');

    expect($house->head)->toBeInstanceOf(Character::class);
    expect($house->head->id)->toEqual('TywinLannister');
});

test('whereHas', function () {
    $locations = Location::whereHas('leader', function (Builder $query) {
        $query->where('age', '<', 30);
    })
        ->distinct()
        ->pluck('led_by');

    expect($locations->count())->toBe(2);
    expect($locations[0])->toBe('DaenerysTargaryen');
    expect($locations[1])->toBe('SansaStark');
});

test('orWhereHas', function () {
    $locations = Location::where(function (Builder $query) {
        $query->whereHas('leader', function (Builder $query) {
            $query->where('age', '<', 15);
        })->orWhereHas('leader', function (Builder $query) {
            $query->where('age', '>', 30);
        });
    })
        ->distinct()
        ->pluck('led_by');

    expect($locations->count())->toBe(2);
    expect($locations[0])->toBe('CerseiLannister');
    expect($locations[1])->toBe('SansaStark');
});

test('orWhereDoesntHave', function () {
    $houses = House::where(function (Builder $query) {
        $query->whereDoesntHave('head', function (Builder $query) {
            $query->where('age', '<', 20);
        })
            ->orWhereDoesntHave('head', function (Builder $query) {
                $query->whereNull('age');
            });
    })->get();

    expect($houses[0]->name)->toBe('Stark');
    expect($houses[1]->name)->toBe('Targaryen');
});


test('whereRelation', function () {
    $locations = Location::whereRelation('leader', 'age', '<', 30)
        ->distinct()
        ->pluck('led_by');

    expect($locations->count())->toBe(2);
    expect($locations[0])->toBe('DaenerysTargaryen');
    expect($locations[1])->toBe('SansaStark');
});

test('orWhereRelation', function () {
    $locations = Location::where(function (Builder $query) {
        $query->whereRelation('leader', 'age', '<', 15)
            ->orWhereRelation('leader', 'age', '>', 30);
    })
        ->distinct()
        ->pluck('led_by');

    expect($locations->count())->toBe(2);
    expect($locations[0])->toBe('CerseiLannister');
    expect($locations[1])->toBe('SansaStark');
});

test('whereDoesntHaveRelation', function () {
    $characters = Character::whereDoesntHaveRelation('leads', 'name', 'Astapor')->get();

    $daenarys = $characters->first(function (Character $character) {
        return $character->id === 'DaenerysTargaryen';
    });

    expect($characters->count())->toBe(42);
    expect($daenarys)->toBeNull();
});

test('orWhereDoesntHaveRelation', function () {
    $houses = House::where(function (Builder $query) {
        $query->whereDoesntHaveRelation('head', 'age', '<', 20)
            ->orWhereDoesntHaveRelation('head', 'age', null);
    })->get();

    expect($houses[0]->name)->toBe('Stark');
    expect($houses[1]->name)->toBe('Targaryen');
});