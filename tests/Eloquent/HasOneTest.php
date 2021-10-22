<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

class HasOneTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
    }

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
                    'location_id' => 'locations/kingslanding',
                ],
                [
                    '_key'         => 'SansaStark',
                    'name'         => 'Sansa',
                    'surname'      => 'Stark',
                    'alive'        => true,
                    'age'          => 13,
                    'traits'       => ['D', 'I', 'J'],
                    'location_id' => 'locations/winterfell',
                ],
                [
                    '_key'         => 'RobertBaratheon',
                    'name'         => 'Robert',
                    'surname'      => 'Baratheon',
                    'alive'        => false,
                    'age'          => null,
                    'traits'       => ['A', 'H', 'C'],
                    'location_id' => 'locations/dragonstone',
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
                    'led_by'     => 'characters/SansaStark',
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
        $location = Location::find('locations/kingslanding');
        $character = $location->character;

        $this->assertEquals('kingslanding', $location->_key);
        $this->assertEquals($character->location_id, $location->_id);
        $this->assertInstanceOf(Character::class, $character);
    }

    public function testAlternativeRelationshipNameAndKey()
    {
        $character = Character::find('characters/SansaStark');
        $location = $character->leads;

        $this->assertEquals('winterfell', $location->_key);
        $this->assertEquals($character->location_id, $location->_id);
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
        $character = Character::find('characters/TheonGreyjoy');
        $location->leader()->associate($character);

        $location->push();

        $location = Location::find('locations/pyke');

        $this->assertEquals('TheonGreyjoy', $location->leader->_key);
        $this->assertEquals($location->led_by, 'characters/TheonGreyjoy');
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
        $location = Location::create(
            [
                '_key'       => 'pyke',
                'name'       => 'Pyke',
                'coordinate' => [55.8833342, -6.1388807],
            ]
        );

        $location->character()->save($character);

        $character = $location->character;

        $this->assertEquals('TheonGreyjoy', $character->_key);
        $this->assertEquals($character->location_id, $location->_id);
        $this->assertInstanceOf(Character::class, $character);
    }

    public function testWith(): void
    {
        $character = Character::with('leads')->find('characters/SansaStark');

        $this->assertInstanceOf(Location::class, $character->leads);
        $this->assertEquals('winterfell', $character->leads->_key);
    }
}
