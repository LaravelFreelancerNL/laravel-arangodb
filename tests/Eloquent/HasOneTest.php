<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

class HasOneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        Character::insert(
            [
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
            ]
        );
        Location::insert(
            [
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
            ]
        );
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
        $location = Location::find('kingslanding');
        $character = $location->character;

        $this->assertEquals('kingslanding', $location->_key);
        $this->assertEquals($character->location_key, $location->_key);
        $this->assertInstanceOf(Character::class, $character);
    }

    public function testAlternativeRelationshipNameAndKey()
    {
        $character = Character::find('SansaStark');
        $location = $character->leads;

        $this->assertEquals('winterfell', $location->_key);
        $this->assertEquals($character->location_key, $location->_key);
        $this->assertInstanceOf(Location::class, $location);
    }

    public function testCreate()
    {
        $location = Location::create(
            [
                '_key'       => 'pyke',
                'name'       => 'Pyke',
                'coordinate' => [55.8833342, -6.1388807],
            ]
        );

        $location->leader()->create(
            [
                '_key'    => 'TheonGreyjoy',
                'name'    => 'Theon',
                'surname' => 'Greyjoy',
                'alive'   => true,
                'age'     => 16,
                'traits'  => ['E', 'R', 'K'],
            ]
        );
        $character = Character::find('TheonGreyjoy');
        $location->leader()->associate($character);

        $location->push();

        $location = Location::find('pyke');

        $this->assertEquals('TheonGreyjoy', $location->leader->_key);
        $this->assertEquals($location->led_by, 'TheonGreyjoy');
        $this->assertInstanceOf(Character::class, $location->leader);
    }

    public function testSave()
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

        $location->character()->save($character);

        $character = $location->character;

        $this->assertEquals('TheonGreyjoy', $character->_key);
        $this->assertEquals($character->location_key, $location->_key);
        $this->assertInstanceOf(Character::class, $character);
    }

    public function testWith(): void
    {
        $character = Character::with('leads')->find('SansaStark');

        $this->assertInstanceOf(Location::class, $character->leads);
        $this->assertEquals('winterfell', $character->leads->_key);
    }
}
