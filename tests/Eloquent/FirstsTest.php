<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;


beforeEach(function () {
    Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
});

test('first', function () {
    $ned = Character::first();

    expect($ned->id)->toEqual('NedStark');
});

test('first with columns', function () {
    $ned = Character::first(["_id", "name"]);

    expect($ned->name)->toEqual('Ned');
    expect($ned->toArray())->toHaveCount(2);
});

test('first or', function () {
    $ned = Character::firstOr(function () {
        return false;
    });

    expect($ned->name)->toEqual('Ned');
});

test('first or trigger or', function () {
    $location = Location::firstOr(function () {
        return false;
    });

    expect($location)->toBeFalse();
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

    expect($model->_id)->toBe($ned->_id);
    expect($model->id)->toBe($ned->id);
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

    expect($model->_id)->toBe($ned->_id);
    expect($model->en->description)->toBe($char['en']['description']);
    expect($model->en->quotes)->toBe($char['en']['quotes']);
});

test('first or fail', function () {
    $ned = Character::firstOrFail();

    expect($ned->id)->toEqual('NedStark');
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

    expect($model->id)->toBe($ned->id);
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

    expect($dragonstone)->toBeNull();
    $this->assertObjectNotHasAttribute('id', $model);
});

test('first where', function () {
    $result = Character::firstWhere('name', 'Jorah');

    expect($result)->toBeInstanceOf(Character::class);
    expect($result->name)->toBe('Jorah');
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
