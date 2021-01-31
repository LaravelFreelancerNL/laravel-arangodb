<?php

namespace Tests\Query;

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

class RelationshipQueriesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
        Artisan::call('db:seed', ['--class' => LocationsSeeder::class]);
        Artisan::call('db:seed', ['--class' => TagsSeeder::class]);
        Artisan::call('db:seed', ['--class' => TaggablesSeeder::class]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow(null);
        Carbon::resetToStringFormat();
        Model::unsetEventDispatcher();
        M::close();
    }

    public function testHas()
    {
        $characters = Character::has('leads')->get();
        $this->assertEquals(3, count($characters));
    }

    public function testHasWithMinimumRelationCount()
    {
        $characters = Character::has('leads', '>=', 3)->get();
        $this->assertEquals(1, count($characters));
    }

    public function testHasMorph()
    {
        $characters = Character::has('tags')->get();

        $this->assertEquals(2, count($characters));
    }

    public function testDoesntHave()
    {
        $characters = Character::doesntHave('leads')->get();
        $this->assertEquals(40, count($characters));
    }

    public function testWithCount()
    {
        $characters = Character::withCount('leads')
            ->where('leads_count', '>', 0)
            ->get();

        $this->assertEquals(3, count($characters));
    }
}
