<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

class BelongsToManyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\CharactersSeeder::class]);
        Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\LocationsSeeder::class]);
        Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\ChildrenSeeder::class]);
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

        $this->assertEquals(1, count($parents));
        $this->assertInstanceOf(Character::class, $parents[0]);
        $this->assertEquals('characters/NedStark', $parents[0]->_id);

        $this->assertTrue(true);
    }


    public function testAttach()
    {
        $child = Character::find('JonSnow');

        $lyannaStark = Character::create(
            [
                "_key" => "LyannaStark",
                "name" => "Lyanna",
                "surname" => "Stark",
                "alive" => false,
                "age" => 25,
                "residence_key" => "winterfell"
            ]
        );

        // Reload from DB
        $lyannaStark = Character::find('LyannaStark');

        $child->parents()->attach($lyannaStark);
        $child->save();

        $reloadedChild = Character::find('JonSnow');
        $parents = $child->parents;

        $this->assertEquals('NedStark', $parents[0]->_key);
        $this->assertEquals('LyannaStark', $parents[1]->_key);
    }

    public function testDetach()
    {
        $child = Character::find('JonSnow');

        $child->parents()->detach('characters/NedStark');
        $child->save();

        $reloadedChild = Character::find('JonSnow');
        $this->assertEquals(0, count($reloadedChild->parents));
    }

    public function testSync(): void
    {
        $lyannaStark = Character::create(
            [
                "_key" => "LyannaStark",
                "name" => "Lyanna",
                "surname" => "Stark",
                "alive" => false,
                "age" => 25,
                "residence_key" => "winterfell"
            ]
        );
        $rhaegarTargaryen = Character::create(
            [
                "_key" => "RhaegarTargaryen",
                "name" => "Rhaegar",
                "surname" => "Targaryen",
                "alive" => false,
                "age" => 25,
                "residence_key" => "dragonstone"
            ]
        );

        $child = Character::find('JonSnow');

        $child->parents()->sync(['characters/LyannaStark', 'characters/RhaegarTargaryen']);
        $reloadedChild = Character::find('JonSnow');

        $this->assertEquals(2, count($reloadedChild->parents));
        $this->assertEquals( 'characters/LyannaStark',$reloadedChild->parents[0]->_id);
        $this->assertEquals( 'characters/RhaegarTargaryen',$reloadedChild->parents[1]->_id);
    }
}
