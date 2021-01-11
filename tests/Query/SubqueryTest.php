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
use Tests\Setup\Models\Character;
use Tests\TestCase;

class SubqueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
        Artisan::call('db:seed', ['--class' => TagsSeeder::class]);
        Artisan::call('db:seed', ['--class' => LocationsSeeder::class]);
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
                ->whereColumn('locations.led_by', 'characters._key')
                ->limit(1);
        }, 'The Red Keep')->get();

        dd($characters);


//        $this->assertCount(15, $characters);
//        $this->assertEquals('NedStark', $characters[0]->_key);
    }
}
