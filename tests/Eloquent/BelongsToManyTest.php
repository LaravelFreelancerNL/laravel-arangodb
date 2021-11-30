<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Database\Seeds\ChildrenSeeder;
use Tests\Setup\Database\Seeds\LocationsSeeder;
use Tests\setup\Models\Character;
use Tests\TestCase;

class BelongsToManyTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

        Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
        Artisan::call('db:seed', ['--class' => ChildrenSeeder::class]);
        Artisan::call('db:seed', ['--class' => LocationsSeeder::class]);
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
        $parent = Character::find('NedStark');

        $children = $parent->children;

        $this->assertEquals(5, count($children));
        $this->assertInstanceOf(Character::class, $children[0]);
        $this->assertEquals('characters/NedStark', $children[0]->pivot->_from);

        $this->assertTrue(true);
    }

    public function testInverseRelation()
    {
        $child = Character::find('JonSnow');

        $parents = $child->parents;
        $this->assertCount(1, $parents);
        $this->assertInstanceOf(Character::class, $parents[0]);
        $this->assertEquals('characters/NedStark', $parents[0]->_id);

        $this->assertTrue(true);
    }


    public function testAttach()
    {
        $child = Character::find('JonSnow');

        $lyannaStark = Character::firstOrCreate(
            [
                "id" => "LyannaStark",
                "name" => "Lyanna",
                "surname" => "Stark",
                "alive" => false,
                "age" => 25,
                "residence_id" => "winterfell"
            ]
        );

        // Reload from DB
        $lyannaStark = Character::find('LyannaStark');

        $child->parents()->attach($lyannaStark);
        $child->save();

        $reloadedChild = Character::find('JonSnow');
        $parents = $child->parents;

        $this->assertEquals('NedStark', $parents[0]->id);
        $this->assertEquals('LyannaStark', $parents[1]->id);

        $child->parents()->detach($lyannaStark);
        $child->save();
        $lyannaStark->delete();
    }

    public function testDetach()
    {
        $child = Character::find('JonSnow');

        $child->parents()->detach('characters/NedStark');
        $child->save();

        $child = $child->fresh();

        $this->assertCount(0, $child->parents);
    }

    public function testSync(): void
    {
        $lyannaStark = Character::firstOrCreate(
            [
                "id" => "LyannaStark",
                "name" => "Lyanna",
                "surname" => "Stark",
                "alive" => false,
                "age" => 25,
                "residence_id" => "winterfell"
            ]
        );
        $rhaegarTargaryen = Character::firstOrCreate(
            [
                "id" => "RhaegarTargaryen",
                "name" => "Rhaegar",
                "surname" => "Targaryen",
                "alive" => false,
                "age" => 25,
                "residence_id" => "dragonstone"
            ]
        );

        $child = Character::find('JonSnow');

        $child->parents()->sync(['characters/LyannaStark', 'characters/RhaegarTargaryen']);
        $child->fresh();

        $this->assertEquals(2, count($child->parents));
        $this->assertEquals('characters/LyannaStark', $child->parents[0]->_id);
        $this->assertEquals('characters/RhaegarTargaryen', $child->parents[1]->_id);
        $this->assertEquals('LyannaStark', $child->parents[0]->id);
        $this->assertEquals('RhaegarTargaryen', $child->parents[1]->id);

        $child->parents()->sync('characters/NedStark');
        $rhaegarTargaryen->delete();
        $lyannaStark->delete();
    }
}
