<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    M::close();
});

test('group by', function () {
    $surnames = DB::table('characters')
        ->select('surname')
        ->groupBy('surname')
        ->get();

    $this->assertCount(21, $surnames);
    $this->assertEquals('Baelish', $surnames[1]);
});

test('group by multiple', function () {
    $groups = DB::table('characters')
        ->select('surname', 'residence_id')
        ->groupBy('surname', 'residence_id')
        ->get();

    $this->assertCount(29, $groups);
    $this->assertEquals(null, $groups[4][0]);
    $this->assertEquals('the-red-keep', $groups[4][1]);
    $this->assertEquals('Baelish', $groups[5][0]);
    $this->assertEquals('the-red-keep', $groups[5][1]);
});

test('having', function () {
    $surnames = DB::table('characters')
        ->select('surname')
        ->groupBy('surname')
        ->having('surname', 'LIKE', '%S%')
        ->get();

    $this->assertCount(4, $surnames);
    $this->assertEquals('Seaworth', $surnames[1]);
});

test('or having', function () {
    $ages = DB::table('characters')
        ->select('age')
        ->groupBy('age')
        ->having('age', '<', 20)
        ->orHaving('age', '>', 40)
        ->get();

    $this->assertCount(9, $ages);
    $this->assertEquals(10, $ages[1]);
});

test('having between', function () {
    $ages = DB::table('characters')
        ->select('age')
        ->groupBy('age')
        ->havingBetween('age', [20, 40])
        ->get();

    $this->assertCount(3, $ages);
    $this->assertEquals(36, $ages[1]);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
}
