<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

class BelongsToTest extends TestCase
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

        $this->assertInstanceOf(Character::class, $children[0]);

        $this->assertTrue(true);
    }

    public function testAlternativeRelationshipNameAndKey()
    {
        $location = Location::find('winterfell');
        $character = $location->leader;

        $this->assertEquals('SansaStark', $character->_key);
        $this->assertEquals($location->led_by, $character->_key);
        $this->assertInstanceOf(Character::class, $character);
    }

    public function testAssociate()
    {
        $character = Character::find('TheonGreyjoy');

        $location = new Location(
            [
                "_key" => "pyke",
                "name" => "Pyke",
                "coordinate" => [55.8833342, -6.1388807]
            ]
        );

        $location->leader()->associate($character);
        $location->save();

        $character = Character::find('TheonGreyjoy');

        $location = $character->leads;

        $this->assertEquals('pyke', $location->_key);
        $this->assertEquals($location->led_by, $character->_key);
        $this->assertInstanceOf(Location::class, $location);
    }

    public function testDissociate()
    {
        $character = Character::find('NedStark');
        $this->assertEquals($character->residence_key, 'winterfell');

        $character->residence()->dissociate();
        $character->save();

        $character = Character::find('NedStark');
        $this->assertNull($character->residence_key);
    }

    public function testWith(): void
    {
        $location = Location::with('leader')->find("winterfell");

        $this->assertInstanceOf(Character::class, $location->leader);
        $this->assertEquals('SansaStark', $location->leader->_key);
    }
}
