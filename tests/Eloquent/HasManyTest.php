<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

class HasManyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        Character::insert(
            [
                [
                    '_key'          => 'NedStark',
                    'name'          => 'Ned',
                    'surname'       => 'Stark',
                    'alive'         => false,
                    'age'           => 41,
                    'traits'        => ['A', 'H', 'C', 'N', 'P'],
                    'residence_key' => 'winterfell',
                ],
                [
                    '_key'          => 'SansaStark',
                    'name'          => 'Sansa',
                    'surname'       => 'Stark',
                    'alive'         => true,
                    'age'           => 13,
                    'traits'        => ['D', 'I', 'J'],
                    'residence_key' => 'winterfell',
                ],
                [
                    '_key'          => 'RobertBaratheon',
                    'name'          => 'Robert',
                    'surname'       => 'Baratheon',
                    'alive'         => false,
                    'age'           => null,
                    'traits'        => ['A', 'H', 'C'],
                    'residence_key' => 'dragonstone',
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
        $location = Location::find('winterfell');

        $inhabitants = $location->inhabitants;

        $this->assertCount(2, $inhabitants);
        $this->assertEquals($inhabitants->first()->residence_key, $location->_key);
        $this->assertInstanceOf(Character::class, $inhabitants->first());
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

        $location->inhabitants()->save($character);

        // Reload
        $location = Location::findOrFail('pyke');

        $inhabitants = $location->inhabitants;

        $this->assertEquals('TheonGreyjoy', $inhabitants->first()->_key);
        $this->assertEquals($inhabitants->first()->residence_key, $location->_key);
        $this->assertInstanceOf(Character::class, $inhabitants->first());
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

        $location->inhabitants()->create(
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
        $location = Location::find('pyke');

        $this->assertEquals('TheonGreyjoy', $location->inhabitants->first()->_key);
        $this->assertEquals($character->residence_key, 'pyke');
        $this->assertInstanceOf(Character::class, $location->inhabitants->first());
    }

    public function testWith(): void
    {
        $location = Location::with('inhabitants')->find('winterfell');

        $this->assertInstanceOf(Character::class, $location->inhabitants->first());
        $this->assertEquals('NedStark', $location->inhabitants->first()->_key);
    }
}
