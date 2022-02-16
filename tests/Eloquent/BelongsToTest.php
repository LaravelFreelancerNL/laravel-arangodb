<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Database\Seeds\ChildrenSeeder;
use Tests\Setup\Database\Seeds\LocationsSeeder;
use Tests\setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

uses(TestCase::class);

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

    $this->assertInstanceOf(Character::class, $children[0]);

    $this->assertTrue(true);
});

test('alternative relationship name and key', function () {
    $location = Location::find('winterfell');
    $character = $location->leader;

    $this->assertEquals('SansaStark', $character->id);
    $this->assertEquals($location->led_by, $character->id);
    $this->assertInstanceOf(Character::class, $character);
});

test('associate', function () {
    $character = Character::find('TheonGreyjoy');

    $location = new Location(
        [
            "id" => "pyke",
            "name" => "Pyke",
            "coordinate" => [55.8833342, -6.1388807]
        ]
    );

    $location->leader()->associate($character);
    $location->save();

    $character->fresh();

    $location = $character->leads;

    $this->assertEquals('pyke', $location->id);
    $this->assertEquals($location->led_by, $character->id);
    $this->assertInstanceOf(Location::class, $location);

    $location->delete();
});

test('dissociate', function () {
    $character = Character::find('NedStark');
    $this->assertEquals($character->residence_id, 'winterfell');

    $character->residence()->dissociate();
    $character->save();

    $character ->fresh();
    $this->assertNull($character->residence_id);
});

test('with', function () {
    $location = Location::with('leader')->find("winterfell");

    $this->assertInstanceOf(Character::class, $location->leader);
    $this->assertEquals('SansaStark', $location->leader->id);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
    Artisan::call('db:seed', ['--class' => ChildrenSeeder::class]);
    Artisan::call('db:seed', ['--class' => LocationsSeeder::class]);
}
