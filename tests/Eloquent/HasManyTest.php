<?php

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    Character::insert(
        [
            [
                '_key'          => 'NedStark',
                'name'          => 'Ned',
                'surname'       => 'Stark',
                'alive'         => false,
                'age'           => 41,
                'traits'        => ['A', 'H', 'C', 'N', 'P'],
                'residence_id' => 'winterfell',
            ],
            [
                '_key'          => 'SansaStark',
                'name'          => 'Sansa',
                'surname'       => 'Stark',
                'alive'         => true,
                'age'           => 13,
                'traits'        => ['D', 'I', 'J'],
                'residence_id' => 'winterfell',
            ],
            [
                '_key'          => 'RobertBaratheon',
                'name'          => 'Robert',
                'surname'       => 'Baratheon',
                'alive'         => false,
                'age'           => null,
                'traits'        => ['A', 'H', 'C'],
                'residence_id' => 'dragonstone',
            ],
        ]
    );
    Location::insert(
        [
            [
                '_key'       => 'dragonstone',
                'name'       => 'Dragonstone',
                'coordinate' => [55.167801, -6.815096],
            ],
            [
                '_key'       => 'winterfell',
                'name'       => 'Winterfell',
                'coordinate' => [54.368321, -5.581312],
                'led_by'     => 'SansaStark',
            ],
            [
                '_key'       => 'kingslanding',
                'name'       => "King's Landing",
                'coordinate' => [42.639752, 18.110189],
            ],
        ]
    );
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
    expect($inhabitants)->toHaveCount(2);
    expect($location->id)->toEqual($inhabitants->first()->residence_id);
    expect($inhabitants->first())->toBeInstanceOf(Character::class);
});

test('save', function () {
    $character = Character::create(
        [
            'id'    => 'TheonGreyjoy',
            'name'    => 'Theon',
            'surname' => 'Greyjoy',
            'alive'   => true,
            'age'     => 16,
            'traits'  => ['E', 'R', 'K'],
        ]
    );
    $location = Location::create(
        [
            'id'       => 'pyke',
            'name'       => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );
    $location->inhabitants()->save($character);
    // Reload
    $location->fresh();

    $inhabitants = $location->inhabitants;

    expect($inhabitants)->toHaveCount(1);
    expect($inhabitants->first()->id)->toEqual('TheonGreyjoy');
    expect($location->id)->toEqual($inhabitants->first()->residence_id);
    expect($inhabitants->first())->toBeInstanceOf(Character::class);
});

test('create', function () {
    $location = Location::create(
        [
            'id'       => 'pyke',
            'name'       => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );

    $location->inhabitants()->create(
        [
            'id'    => 'TheonGreyjoy',
            'name'    => 'Theon',
            'surname' => 'Greyjoy',
            'alive'   => true,
            'age'     => 16,
            'traits'  => ['E', 'R', 'K'],
        ]
    );
    $character = Character::find('TheonGreyjoy');
    $location = Location::find('pyke');

    expect($location->inhabitants->first()->id)->toEqual('TheonGreyjoy');
    expect('pyke')->toEqual($character->residence_id);
    expect($location->inhabitants->first())->toBeInstanceOf(Character::class);
});

test('first or create', function () {
    $location = Location::create(
        [
            'id'       => 'pyke',
            'name'       => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );

    $location->inhabitants()->firstOrCreate(
        [
            'id'    => 'TheonGreyjoy',
            'name'    => 'Theon',
            'surname' => 'Greyjoy',
            'alive'   => true,
            'age'     => 16,
            'traits'  => ['E', 'R', 'K'],
        ]
    );
    $character = Character::find('TheonGreyjoy');
    $location = Location::find('pyke');

    expect($location->inhabitants->first()->id)->toEqual('TheonGreyjoy');
    expect('pyke')->toEqual($character->residence_id);
    expect($location->inhabitants->first())->toBeInstanceOf(Character::class);
});

test('first or new', function () {
    $location = Location::create(
        [
            'id'       => 'pyke',
            'name'       => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );

    $character = $location->inhabitants()->firstOrNew(
        [
            'id'    => 'TheonGreyjoy',
            'name'    => 'Theon',
            'surname' => 'Greyjoy',
            'alive'   => true,
            'age'     => 16,
            'traits'  => ['E', 'R', 'K'],
        ]
    );

    expect($character)->toBeInstanceOf(Character::class);
    expect($character->id)->toEqual('TheonGreyjoy');
    expect('pyke')->toEqual($character->residence_id);
});

test('with', function () {
    $location = Location::with('inhabitants')->find('winterfell');

    expect($location->inhabitants->first())->toBeInstanceOf(Character::class);
    expect($location->inhabitants->first()->id)->toEqual('NedStark');
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
