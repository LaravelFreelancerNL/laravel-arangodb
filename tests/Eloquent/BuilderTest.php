<?php

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());
});

afterEach(function () {
    Carbon::setTestNow(null);
    Carbon::resetToStringFormat();
    Model::unsetEventDispatcher();

    M::close();
});

test('insert handles array attributes', function () {
    $character = [
        'en' => [
            'titles' => ['Lord of Winterfell', 'Hand of the king'],
        ],
        '_key'         => 'NedStark',
        'name'         => 'Ned',
        'surname'      => 'Stark',
        'alive'        => false,
        'age'          => 41,
        'location_id' => 'locations/kingslanding',
    ];
    $ned = Character::find('NedStark');
    if ($ned === null) {
        Character::insert($character);
    } else {
        $ned->update($character);
    }

    $ned = Character::find('NedStark');

    $this->assertEquals('NedStark', $ned->id);
    $this->assertIsObject($ned->en);
    $this->assertEquals($character['en'], (array) $ned->en);
    $this->assertInstanceOf(Character::class, $ned);
});

test('update sets correct updated at', function () {
    $character = [
        'en' => [
            'titles' => ['Lord of Winterfell', 'Hand of the king'],
        ],
        '_key'         => 'NedStark',
        'name'         => 'Ned',
        'surname'      => 'Stark',
        'alive'        => true,
        'age'          => 41,
        'location_id' => 'locations/kingslanding',
    ];
    $ned = Character::find('characters/NedStark');
    if ($ned === null) {
        Character::insert($character);
    } else {
        $ned->update($character);
    }
    $retrievedBeforeUpdate = Character::find('NedStark');
    $retrievedBeforeUpdate->update(['alive' => false]);

    $retrievedAfterUpdate = Character::find('NedStark');
    $this->assertArrayNotHasKey('characters.updated_at', $retrievedAfterUpdate->toArray());
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
