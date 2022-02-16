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
        { "id": "RamsayBolton", "name": "Ramsay", "surname": "Bolton", "alive": true, '
        . '"traits": ["E","O","G","A"] },
        { "id": "SansaStark", "name": "Sansa", "surname": "Stark", "alive": true, "age": 13, '
        . '"traits": ["D","I","J"] },
        { "id": "RickonStark", "name": "Bran", "surname": "Stark", "alive": true, "age": 10, '
        . '"traits": ["R"] },
        { "id": "TheonGreyjoy", "name": "Theon", "surname": "Greyjoy", "alive": true, "age": 16, '
        . '"traits": ["E","R","K"] }
    ]';
    $characters = json_decode($characters, JSON_OBJECT_AS_ARRAY);
    foreach ($characters as $character) {
        Character::insert($character);
    }

    Location::insert(
        [
            [
                'id'            => 'winterfell',
                'name'            => 'Winterfell',
                'coordinate'      => [54.368321, -5.581312],
                'capturable_id'   => 'RamsayBolton',
                'capturable_type' => 'Tests\Setup\Models\Character',
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
    $character = Character::find('RamsayBolton');
    $location = $character->conquered;

    expect($location->id)->toEqual('winterfell');
    expect($character->id)->toEqual($location->capturable_id);
    expect($character)->toBeInstanceOf(Character::class);
});

test('save', function () {
    $location = Location::find('winterfell');
    $character = Character::find('TheonGreyjoy');

    $character->conquered()->save($location);
    $location = Location::find('winterfell');

    expect($location->capturable_id)->toEqual($character->id);
    expect($character->conquered)->toBeInstanceOf(Location::class);
});

test('with', function () {
    $character = Character::with('conquered')->find('RamsayBolton');

    expect($character->conquered)->toBeInstanceOf(Location::class);
    expect($character->conquered->id)->toEqual('winterfell');
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
