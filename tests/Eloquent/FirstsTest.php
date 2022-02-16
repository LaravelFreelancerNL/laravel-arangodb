<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
});

test('first', function () {
    $ned = Character::first();

    $this->assertEquals('NedStark', $ned->id);
});

test('first with columns', function () {
    $ned = Character::first(["_id", "name"]);

    $this->assertEquals('Ned', $ned->name);
    $this->assertCount(2, $ned->toArray());
});

test('first or', function () {
    $ned = Character::firstOr(function () {
        return false;
    });

    $this->assertEquals('Ned', $ned->name);
});

test('first or trigger or', function () {
    $location = Location::firstOr(function () {
        return false;
    });

    $this->assertFalse($location);
});

test('first or create', function () {
    $ned = Character::find('NedStark');

    $char = [
        "_key" => "NedStark",
        "name" => "Ned",
        "surname" => "Stark",
        "alive" => true,
        "age" => 41,
        "residence_id" => "winterfell"
    ];

    $model = Character::firstOrCreate($char);

    $this->assertSame($ned->_id, $model->_id);
    $this->assertSame($ned->id, $model->id);
});

test('first or create with nested data', function () {
    $char = [
        "_key" => "NedStark",
        "name" => "Ned",
        "surname" => "Stark",
        "alive" => true,
        "age" => 41,
        "residence_id" => "winterfell",
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

    $ned = Character::find('NedStark');
    $ned->en = $char['en'];
    $ned->save();

    $model = Character::firstOrCreate(
        $char,
        $char
    );

    $this->assertSame($ned->_id, $model->_id);
    $this->assertSame($char['en']['description'], $model->en->description);
    $this->assertSame($char['en']['quotes'], $model->en->quotes);
});

test('first or fail', function () {
    $ned = Character::firstOrFail();

    $this->assertEquals('NedStark', $ned->id);
});

test('first or failing', function () {
    $this->expectException(ModelNotFoundException::class);

    Location::firstOrFail();
});

test('first or new', function () {
    $ned = Character::find('NedStark');

    $char = [
        "_key" => "NedStark",
        "name" => "Ned",
        "surname" => "Stark",
        "alive" => true,
        "age" => 41,
        "residence_id" => "winterfell"
    ];

    $model = Character::firstOrNew($char);

    $this->assertSame($ned->id, $model->id);
});

test('first or new none existing', function () {
    $dragonstone = Location::find('dragonstone');

    $loc = [
        "_key" => "dragonstone",
        "name" => "Dragonstone",
        "coordinate" => [
            55.167801,
            -6.815096
        ],
        "led_by" => "DaenerysTargaryen"
    ];

    $model = Location::firstOrNew($loc);

    $this->assertNull($dragonstone);
    $this->assertObjectNotHasAttribute('id', $model);
});

test('first where', function () {
    $result = Character::firstWhere('name', 'Jorah');

    $this->assertInstanceOf(Character::class, $result);
    $this->assertSame('Jorah', $result->name);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
