<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as m;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Database\Seeds\LocationsSeeder;
use Tests\Setup\Database\Seeds\TaggablesSeeder;
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

test('has', function () {
    $characters = Character::has('leads')->get();
    $this->assertEquals(3, count($characters));
});

test('has with minimum relation count', function () {
    $characters = Character::has('leads', '>=', 3)->get();
    $this->assertEquals(1, count($characters));
});

test('has morph', function () {
    $characters = Character::has('tags')->get();

    $this->assertEquals(2, count($characters));
});

test('doesnt have', function () {
    $characters = Character::doesntHave('leads')->get();
    $this->assertEquals(40, count($characters));
});

test('with count', function () {
    $characters = Character::withCount('leads')
        ->where('leads_count', '>', 0)
        ->get();

    $this->assertEquals(3, count($characters));
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
    Artisan::call('db:seed', ['--class' => LocationsSeeder::class]);
    Artisan::call('db:seed', ['--class' => TagsSeeder::class]);
    Artisan::call('db:seed', ['--class' => TaggablesSeeder::class]);
}
