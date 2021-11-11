<?php

declare(strict_types=1);

namespace Tests\Eloquent;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

class FirstsTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
    }

    public function testFirst()
    {
        $ned = Character::first();

        $this->assertEquals('characters/NedStark', $ned->id);
    }

    public function testFirstWithColumns()
    {
        $ned = Character::first(["_id", "name"]);

        $this->assertEquals('Ned', $ned->name);
        $this->assertCount(2, $ned->toArray());
    }

    public function testFirstOr()
    {
        $ned = Character::firstOr(function () {
            return false;
        });

        $this->assertEquals('Ned', $ned->name);
    }

    public function testFirstOrTriggerOr()
    {
        $location = Location::firstOr(function () {
            return false;
        });

        $this->assertFalse($location);
    }


    public function testFirstOrCreate()
    {
        $ned = Character::find('characters/NedStark');

        $char = [
            "_key" => "NedStark",
            "name" => "Ned",
            "surname" => "Stark",
            "alive" => true,
            "age" => 41,
            "residence_id" => "locations/winterfell"
        ];

        $model = Character::firstOrCreate($char);

        $this->assertSame($ned->_id, $model->_id);
    }

    public function testFirstOrCreateWithNestedData()
    {
        $char = [
            "_key" => "NedStark",
            "name" => "Ned",
            "surname" => "Stark",
            "alive" => true,
            "age" => 41,
            "residence_id" => "locations/winterfell",
            "en" => [
                "description" => "
                    Lord Eddard Stark, also known as Ned Stark, was the head of House Stark, the Lord of Winterfell, 
                    Lord Paramount and Warden of the North, and later Hand of the King to King Robert I Baratheon. 
                    He was the older brother of Benjen, Lyanna and the younger brother of Brandon Stark. He is the 
                    father of Robb, Sansa, Arya, Bran, and Rickon by his wife, Catelyn Tully, and uncle of Jon Snow, 
                    who he raised as his bastard son. He was a dedicated husband and father, a loyal friend, 
                    and an honorable lord.",
                "quotes" => [
                    "When the snows fall and the white winds blow, the lone wolf dies, but the pack survives."
                ],
            ],
        ];

        $ned = Character::find('characters/NedStark');
        $ned->en = $char['en'];
        $ned->save();

        $model = Character::firstOrCreate(
            $char,
            $char
        );

        $this->assertSame($ned->_id, $model->_id);
        $this->assertSame($char['en']['description'], $model->en->description);
        $this->assertSame($char['en']['quotes'], $model->en->quotes);
    }

    public function testFirstOrFail()
    {
        $ned = Character::firstOrFail();

        $this->assertEquals('characters/NedStark', $ned->id);
    }

    public function testFirstOrFailing()
    {
        $this->expectException(ModelNotFoundException::class);

        Location::firstOrFail();
    }

    public function testFirstOrNew()
    {
        $ned = Character::find('characters/NedStark');

        $char = [
            "_key" => "NedStark",
            "name" => "Ned",
            "surname" => "Stark",
            "alive" => true,
            "age" => 41,
            "residence_id" => "locations/winterfell"
        ];

        $model = Character::firstOrNew($char);

        $this->assertSame($ned->_id, $model->_id);
    }

    public function testFirstOrNewNoneExisting()
    {
        $dragonstone = Location::find('locations/dragonstone');

        $loc = [
            "_key" => "dragonstone",
            "name" => "Dragonstone",
            "coordinate" => [
                55.167801,
                -6.815096
            ],
            "led_by" => "characters/DaenerysTargaryen"
        ];

        $model = Location::firstOrNew($loc);

        $this->assertNull($dragonstone);
        $this->assertObjectNotHasAttribute('_id', $model);
    }

    public function testFirstWhere()
    {
        $result = Character::firstWhere('name', 'Jorah');

        $this->assertInstanceOf(Character::class, $result);
        $this->assertSame('Jorah', $result->name);
    }
}
