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

    $characters = '[
        { "id": "DaenerysTargaryen", "name": "Daenerys", "surname": "Targaryen", "alive": true, "age": 16, '
        . '"traits": ["D","H","C"] },
        { "id": "TheonGreyjoy", "name": "Theon", "surname": "Greyjoy", "alive": true, "age": 16, '
        . '"traits": ["E","R","K"] },
        { "id": "RamsayBolton", "name": "Ramsay", "surname": "Bolton", "alive": true, '
        . '"traits": ["E","O","G","A"] }
    ]';
    $characters = json_decode($characters, JSON_OBJECT_AS_ARRAY);
    foreach ($characters as $character) {
        Character::insert($character);
    }

    $locations = '[
        { "id": "dragonstone", "capturable_id": "DaenerysTargaryen", '
        . '"capturable_type": "Tests\\\Setup\\\Models\\\Character", "name": "Dragonstone", '
        . '"coordinate": [ 55.167801, -6.815096 ] },
        { "id": "king-s-landing", "capturable_id": "DaenerysTargaryen", '
        . '"capturable_type": "Tests\\\Setup\\\Models\\\Character", "name": "King\'s Landing", '
        . '"coordinate": [ 42.639752, 18.110189 ] },
        { "id": "the-red-keep", "capturable_id": "DaenerysTargaryen", '
        . '"capturable_type": "Tests\\\Setup\\\Models\\\Character", "name": "The Red Keep", '
        . '"coordinate": [ 35.896447, 14.446442 ] },
        { "id": "yunkai", "capturable_id": "DaenerysTargaryen", '
        . '"capturable_type": "Tests\\\Setup\\\Models\\\Character", "name": "Yunkai", '
        . '"coordinate": [ 31.046642, -7.129532 ] },
        { "id": "astapor", "capturable_id": "DaenerysTargaryen", '
        . '"capturable_type": "Tests\\\Setup\\\Models\\\Character", "name": "Astapor", '
        . '"coordinate": [ 31.50974, -9.774249 ] },
        { "id": "winterfell", "capturable_id": "TheonGreyjoy", '
        . '"capturable_type": "Tests\\\Setup\\\Models\\\Character", "name": "Winterfell", '
        . '"coordinate": [ 54.368321, -5.581312 ] },
        { "id": "vaes-dothrak", "name": "Vaes Dothrak", "coordinate": [ 54.16776, -6.096125 ] },
        { "id": "beyond-the-wall", "name": "Beyond the wall", "coordinate": [ 64.265473, -21.094093 ] }
    ]';
    $locations = json_decode($locations, JSON_OBJECT_AS_ARRAY);

    foreach ($locations as $location) {
        Location::insert($location);
    }
});

afterEach(function () {
    Carbon::setTestNow(null);
    Carbon::resetToStringFormat();
    Model::unsetEventDispatcher();
    M::close();
});

test('retrieve relation', function () {
    $character = Character::find('DaenerysTargaryen');

    $locations = $character->captured;

    $this->assertInstanceOf(Location::class, $locations->first());
    $this->assertCount(5, $locations);
    $this->assertEquals($locations->first()->capturable->id, $character->id);
});

test('save', function () {
    $location = Location::find('winterfell');
    $this->assertEquals('TheonGreyjoy', $location->capturable_id);

    $character = Character::find('RamsayBolton');
    $character->captured()->save($location);

    $this->assertEquals($character->id, $location->capturable_id);
    $this->assertCount(1, $character->captured);
    $this->assertInstanceOf(Location::class, $character->captured->first());
});

test('with', function () {
    $character = Character::with('captured')->find('TheonGreyjoy');

    $this->assertInstanceOf(Location::class, $character->captured->first());
    $this->assertEquals('winterfell', $character->captured->first()->id);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
