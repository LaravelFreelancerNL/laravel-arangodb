<?php

namespace Tests\Query;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as m;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Database\Seeds\LocationsSeeder;
use Tests\Setup\Database\Seeds\TagsSeeder;
use Tests\TestCase;

class JoinTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

        Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
        Artisan::call('db:seed', ['--class' => TagsSeeder::class]);
        Artisan::call('db:seed', ['--class' => LocationsSeeder::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
        Carbon::resetToStringFormat();

        Model::unsetEventDispatcher();

        M::close();
    }

    public function testJoin()
    {
        $characters = DB::table('characters')
            ->join('locations', 'characters.residence_id', '=', 'locations.id')
            ->where('residence_id', '=', 'winterfell')
            ->get();

        $this->assertCount(15, $characters);
        $this->assertEquals('NedStark', $characters[0]->id);
    }

    public function testCrossJoin()
    {
        $characters = DB::table('characters')
            ->crossJoin('locations')
            ->get();

        $this->assertCount(344, $characters);
    }

    public function testLeftJoin()
    {
        $characters = DB::table('characters')
            ->leftJoin('locations', 'characters.residence_id', '=', 'locations.id')
            ->get();

        $charactersWithoutResidence = DB::table('characters')
            ->whereNull('residence_id')
            ->get();

        $this->assertCount(33, $characters);
        $this->assertEquals('NedStark', $characters[0]->id);
        $this->assertCount(10, $charactersWithoutResidence);
    }
}
