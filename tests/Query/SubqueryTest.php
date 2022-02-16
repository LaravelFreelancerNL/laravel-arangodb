<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as m;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Database\Seeds\LocationsSeeder;
use Tests\Setup\Database\Seeds\TagsSeeder;
use Tests\Setup\Models\Character;
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

test('subquery where', function () {
    $characters = Character::where(function ($query) {
            $query->select('name')
                ->from('locations')
                ->whereColumn('locations.led_by', 'characters.id')
                ->limit(1);
    }, 'Dragonstone')
        ->get();
    $this->assertEquals('DaenerysTargaryen', $characters[0]->id);
});

test('where sub', function () {
    $characters = Character::where('id', '==', function ($query) {
        $query->select('led_by')
            ->from('locations')
            ->where('name', 'Dragonstone')
            ->limit(1);
    })
        ->get();

    $this->assertEquals('DaenerysTargaryen', $characters[0]->id);
});

test('where exists with multiple results', function () {
    $characters = Character::whereExists(function ($query) {
            $query->select('name')
                ->from('locations')
                ->whereColumn('locations.led_by', 'characters.id');
    })
        ->get();
    $this->assertEquals(3, count($characters));
});

test('where exists with limit', function () {
    $characters = Character::whereExists(function ($query) {
            $query->select('name')
                ->from('locations')
                ->whereColumn('locations.led_by', 'characters.id')
                ->limit(1);
    })
        ->get();
    $this->assertEquals(3, count($characters));
});

test('where not exists with multiple results', function () {
    $characters = Character::whereNotExists(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id');
    })
        ->get();
    $this->assertEquals(40, count($characters));
});

test('where not exists with limit', function () {
    $characters = Character::whereNotExists(function ($query) {
            $query->select('name')
                ->from('locations')
                ->whereColumn('locations.led_by', 'characters.id')
                ->limit(1);
    })
        ->get();
    $this->assertEquals(40, count($characters));
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
    Artisan::call('db:seed', ['--class' => TagsSeeder::class]);
    Artisan::call('db:seed', ['--class' => LocationsSeeder::class]);
}
