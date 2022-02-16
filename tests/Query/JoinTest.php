<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as m;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Database\Seeds\LocationsSeeder;
use Tests\Setup\Database\Seeds\TagsSeeder;

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());
});

afterEach(function () {
    Carbon::setTestNow(null);
    Carbon::resetToStringFormat();

    Model::unsetEventDispatcher();

    M::close();
});

test('join', function () {
    $characters = DB::table('characters')
        ->join('locations', 'characters.residence_id', '=', 'locations.id')
        ->where('residence_id', '=', 'winterfell')
        ->get();

    expect($characters)->toHaveCount(15);
    expect($characters[0]->id)->toEqual('NedStark');
});

test('cross join', function () {
    $characters = DB::table('characters')
        ->crossJoin('locations')
        ->get();

    expect($characters)->toHaveCount(344);
});

test('left join', function () {
    $characters = DB::table('characters')
        ->leftJoin('locations', 'characters.residence_id', '=', 'locations.id')
        ->get();

    $charactersWithoutResidence = DB::table('characters')
        ->whereNull('residence_id')
        ->get();

    expect($characters)->toHaveCount(33);
    expect($characters[0]->id)->toEqual('NedStark');
    expect($charactersWithoutResidence)->toHaveCount(10);
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
