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
                'id'         => 'NedStark',
                'name'         => 'Ned',
                'surname'      => 'Stark',
                'alive'        => false,
                'age'          => 41,
                'traits'       => ['A', 'H', 'C', 'N', 'P'],
                'location_id' => 'kingslanding',
            ],
            [
                'id'         => 'SansaStark',
                'name'         => 'Sansa',
                'surname'      => 'Stark',
                'alive'        => true,
                'age'          => 13,
                'traits'       => ['D', 'I', 'J'],
                'location_id' => 'winterfell',
            ],
            [
                'id'         => 'RobertBaratheon',
                'name'         => 'Robert',
                'surname'      => 'Baratheon',
                'alive'        => false,
                'age'          => null,
                'traits'       => ['A', 'H', 'C'],
                'location_id' => 'dragonstone',
            ],
        ]
    );
    Location::insert(
        [
            [
                'id'       => 'dragonstone',
                'name'       => 'Dragonstone',
                'coordinate' => [55.167801, -6.815096],
            ],
            [
                'id'       => 'winterfell',
                'name'       => 'Winterfell',
                'coordinate' => [54.368321, -5.581312],
                'led_by'     => 'SansaStark',
            ],
            [
                'id'       => 'kingslanding',
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
    $location = Location::find('kingslanding');
    $character = $location->character;

    $this->assertEquals('kingslanding', $location->id);
    $this->assertEquals($character->location_id, $location->id);
    $this->assertInstanceOf(Character::class, $character);
});

test('alternative relationship name and key', function () {
    $character = Character::find('SansaStark');
    $location = $character->leads;

    $this->assertEquals('winterfell', $location->id);
    $this->assertEquals($character->location_id, $location->id);
    $this->assertInstanceOf(Location::class, $location);
});

test('create', function () {
    $location = Location::create(
        [
            'id'       => 'pyke',
            'name'       => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );

    $location->leader()->create(
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
    $location->leader()->associate($character);

    $location->push();

    $location = Location::find('pyke');

    $this->assertEquals('TheonGreyjoy', $location->leader->id);
    $this->assertEquals($location->led_by, 'TheonGreyjoy');
    $this->assertInstanceOf(Character::class, $location->leader);
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

    $location->character()->save($character);

    $character = $location->character;

    $this->assertEquals('TheonGreyjoy', $character->id);
    $this->assertEquals($character->location_id, $location->id);
    $this->assertInstanceOf(Character::class, $character);
});

test('with', function () {
    $character = Character::with('leads')->find('SansaStark');

    $this->assertInstanceOf(Location::class, $character->leads);
    $this->assertEquals('winterfell', $character->leads->id);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
