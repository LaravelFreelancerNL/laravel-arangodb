<?php

namespace Tests\Query;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as m;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Database\Seeds\LocationsSeeder;
use Tests\Setup\Database\Seeds\TagsSeeder;
use Tests\Setup\Models\Character;
use Tests\TestCase;

class SubqueryTest extends TestCase
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

    public function testSubqueryWhere()
    {
        $characters = Character::where(function ($query) {
                $query->select('name')
                    ->from('locations')
                    ->whereColumn('locations.led_by', 'characters._id')
                    ->limit(1);
        }, 'Dragonstone')
            ->get();
        $this->assertEquals('DaenerysTargaryen', $characters[0]->_key);
    }

    public function testWhereSub()
    {
        $characters = Character::where('_id', '==', function ($query) {
            $query->select('led_by')
                ->from('locations')
                ->where('name', 'Dragonstone')
                ->limit(1);
        })
            ->get();

        $this->assertEquals('DaenerysTargaryen', $characters[0]->_key);
    }

    public function testWhereExistsWithMultipleResults()
    {
        $characters = Character::whereExists(function ($query) {
                $query->select('name')
                    ->from('locations')
                    ->whereColumn('locations.led_by', 'characters._id');
        })
            ->get();
        $this->assertEquals(3, count($characters));
    }

    public function testWhereExistsWithLimit()
    {
        $characters = Character::whereExists(function ($query) {
                $query->select('name')
                    ->from('locations')
                    ->whereColumn('locations.led_by', 'characters._id')
                    ->limit(1);
        })
            ->get();
        $this->assertEquals(3, count($characters));
    }

    public function testWhereNotExistsWithMultipleResults()
    {
        $characters = Character::whereNotExists(function ($query) {
            $query->select('name')
                ->from('locations')
                ->whereColumn('locations.led_by', 'characters._id');
        })
            ->get();
        $this->assertEquals(40, count($characters));
    }

    public function testWhereNotExistsWithLimit()
    {
        $characters = Character::whereNotExists(function ($query) {
                $query->select('name')
                    ->from('locations')
                    ->whereColumn('locations.led_by', 'characters._id')
                    ->limit(1);
        })
            ->get();
        $this->assertEquals(40, count($characters));
    }
}
