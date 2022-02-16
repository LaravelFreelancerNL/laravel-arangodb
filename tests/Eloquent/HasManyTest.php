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
    $this->assertCount(2, $inhabitants);
    $this->assertEquals($inhabitants->first()->residence_id, $location->id);
    $this->assertInstanceOf(Character::class, $inhabitants->first());
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

    $this->assertCount(1, $inhabitants);
    $this->assertEquals('TheonGreyjoy', $inhabitants->first()->id);
    $this->assertEquals($inhabitants->first()->residence_id, $location->id);
    $this->assertInstanceOf(Character::class, $inhabitants->first());
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

    $this->assertEquals('TheonGreyjoy', $location->inhabitants->first()->id);
    $this->assertEquals($character->residence_id, 'pyke');
    $this->assertInstanceOf(Character::class, $location->inhabitants->first());
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

    $this->assertEquals('TheonGreyjoy', $location->inhabitants->first()->id);
    $this->assertEquals($character->residence_id, 'pyke');
    $this->assertInstanceOf(Character::class, $location->inhabitants->first());
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

    $this->assertInstanceOf(Character::class, $character);
    $this->assertEquals('TheonGreyjoy', $character->id);
    $this->assertEquals($character->residence_id, 'pyke');
});

test('with', function () {
    $location = Location::with('inhabitants')->find('winterfell');

    $this->assertInstanceOf(Character::class, $location->inhabitants->first());
    $this->assertEquals('NedStark', $location->inhabitants->first()->id);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
