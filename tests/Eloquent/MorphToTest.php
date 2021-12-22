<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

class MorphToTest extends TestCase
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

        $characters = '[
            { "id": "RamsayBolton", "name": "Ramsay", "surname": "Bolton", "alive": true, '
            . '"traits": ["E","O","G","A"] },
            { "id": "SansaStark", "name": "Sansa", "surname": "Stark", "alive": true, "age": 13, '
            . '"traits": ["D","I","J"] },
            { "id": "RickonStark", "name": "Bran", "surname": "Stark", "alive": true, "age": 10, '
            . '"traits": ["R"] },
            { "id": "TheonGreyjoy", "name": "Theon", "surname": "Greyjoy", "alive": true, "age": 16, '
            . '"traits": ["E","R","K"] }
        ]';
        $characters = json_decode($characters, JSON_OBJECT_AS_ARRAY);
        foreach ($characters as $character) {
            Character::insert($character);
        }

        Location::insert(
            [
                [
                    'id'            => 'winterfell',
                    'name'            => 'Winterfell',
                    'coordinate'      => [54.368321, -5.581312],
                    'capturable_id'   => 'RamsayBolton',
                    'capturable_type' => "Tests\Setup\Models\Character",
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
        $character = $location->capturable;

        $this->assertEquals('winterfell', $location->id);
        $this->assertEquals($location->capturable_id, $character->id);
        $this->assertInstanceOf(Character::class, $character);
    }

    public function testAssociate()
    {
        $location = Location::find('winterfell');
        $character = Character::find('TheonGreyjoy');

        $location->capturable()->associate($character);
        $location->save();
        $location = Location::find('winterfell');

        $this->assertEquals($character->id, $location->capturable_id);
        $this->assertInstanceOf(Character::class, $location->capturable);
    }

    public function testWith(): void
    {
        $location = Location::with('capturable')->find('winterfell');

        $this->assertInstanceOf(Character::class, $location->capturable);
        $this->assertEquals('RamsayBolton', $location->capturable->id);
    }
}
