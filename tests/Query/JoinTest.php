<?php

namespace Tests\Query;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as m;
use Tests\TestCase;

class JoinTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\CharactersSeeder::class]);
        Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\TagsSeeder::class]);
        Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\LocationsSeeder::class]);
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
            ->join('locations', 'characters.residence_key', '=', 'locations._key')
            ->where('residence_key', '=', 'winterfell')
            ->get();

        $this->assertCount(15, $characters);
        $this->assertEquals('NedStark', $characters[0]['_key']);
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
            ->leftJoin('locations', 'characters.residence_key', '=', 'locations._key')
            ->get();

        $charactersWithoutResidence = DB::table('characters')
            ->whereNull('residence_key')
            ->get();

        $this->assertCount(33, $characters);
        $this->assertEquals('NedStark', $characters[0]['_key']);
        $this->assertCount(10, $charactersWithoutResidence);
    }
}
