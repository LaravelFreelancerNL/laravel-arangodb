<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Database\Seeds\LocationsSeeder;
use Tests\Setup\Database\Seeds\TaggablesSeeder;
use Tests\Setup\Database\Seeds\TagsSeeder;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Tag;
use Tests\TestCase;

class MorphToManyTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

        Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
        Artisan::call('db:seed', ['--class' => LocationsSeeder::class]);
        Artisan::call('db:seed', ['--class' => TagsSeeder::class]);
        Artisan::call('db:seed', ['--class' => TaggablesSeeder::class]);
    }

    public function setUp(): void
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

    public function testRetrieveRelation()
    {
        $character = Character::find('SandorClegane');

        $tags = $character->tags;

        $this->assertEquals(4, count($tags));
        $this->assertInstanceOf(Tag::class, $tags[0]);
    }

    public function testInverseRelation()
    {
        $tag = Tag::find('A');

        $characters = $tag->characters;
        $locations = $tag->locations;

        $this->assertEquals(1, count($characters));
        $this->assertEquals(1, count($locations));
    }


    public function testAttach()
    {
        $character = Character::find('Varys');


        $character->tags()->attach(['B', 'E', 'J', 'N', 'O']);
        $character->save();

        $character->fresh();
        $tags = $character->tags;

        $this->assertEquals(5, count($tags));
        $this->assertEquals('B', $character->tags[0]->id);
    }

    public function testDetach()
    {
        $character = Character::find('SandorClegane');

        $character->tags()->detach(['F', 'K']);
        $character->save();

        $reloadedCharacter = Character::find('SandorClegane');

        $this->assertEquals(2, count($reloadedCharacter->tags));
        $this->assertEquals('A', $reloadedCharacter->tags[0]->id);
        $this->assertEquals('P', $reloadedCharacter->tags[1]->id);
    }

    public function testSync(): void
    {
        $character = Character::find('SandorClegane');

        $character->tags()->sync(['C', 'J']);
        $character->save();

        $reloadedCharacter = Character::find('SandorClegane');

        $this->assertEquals(2, count($reloadedCharacter->tags));
        $this->assertEquals('C', $reloadedCharacter->tags[0]->id);
        $this->assertEquals('J', $reloadedCharacter->tags[1]->id);
    }
}
