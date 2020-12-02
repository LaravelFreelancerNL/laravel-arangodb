<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

class BelongsToTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        $characters = [
            [
                '_key'         => 'NedStark',
                'name'         => 'Ned',
                'surname'      => 'Stark',
                'alive'        => false,
                'age'          => 41,
                'traits'       => ['A', 'H', 'C', 'N', 'P'],
                'location_key' => 'kingslanding',
            ],
            [
                '_key'         => 'SansaStark',
                'name'         => 'Sansa',
                'surname'      => 'Stark',
                'alive'        => true,
                'age'          => 13,
                'traits'       => ['D', 'I', 'J'],
                'location_key' => 'winterfell',
            ],
            [
                '_key'         => 'RobertBaratheon',
                'name'         => 'Robert',
                'surname'      => 'Baratheon',
                'alive'        => false,
                'age'          => null,
                'traits'       => ['A', 'H', 'C'],
                'location_key' => 'dragonstone',
            ],
        ];
        Character::insert($characters);

        $locations = [
            [
                '_key'       => 'dragonstone',
                'name'       => 'Dragonstone',
                'coordinate' => [55.167801, -6.815096],
            ],
            [
                '_key'       => 'winterfell',
                'name'       => 'Winterfell',
                'coordinate' => [54.368321, -5.581312],
                'led_by'     => 'SansaStark',
            ],
            [
                '_key'       => 'kingslanding',
                'name'       => "King's Landing",
                'coordinate' => [42.639752, 18.110189],
            ],
        ];
        Location::insert($locations);
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
        $location = Location::find('winterfell');

        $character = $location->leader;

        $this->assertEquals('SansaStark', $character->_key);
        $this->assertEquals($location->led_by, $character->_key);
        $this->assertInstanceOf(Character::class, $character);
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
        $character = Character::create(
            [
                '_key'    => 'TheonGreyjoy',
                'name'    => 'Theon',
                'surname' => 'Greyjoy',
                'alive'   => true,
                'age'     => 16,
                'traits'  => ['E', 'R', 'K'],
            ]
        );
        $location = new Location(
            [
                '_key'       => 'pyke',
                'name'       => 'Pyke',
                'coordinate' => [55.8833342, -6.1388807],
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
        $this->assertEquals($character->location_key, 'kingslanding');

        $character->location()->dissociate();
        $character->save();

        $character = Character::find('NedStark');
        $this->assertNull($character->location_key);
    }

    public function testWith(): void
    {
        $location = Location::with('leader')->find('winterfell');

        $this->assertInstanceOf(Character::class, $location->leader);
        $this->assertEquals('SansaStark', $location->leader->_key);
    }
}
