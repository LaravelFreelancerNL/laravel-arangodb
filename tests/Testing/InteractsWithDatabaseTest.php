<?php

namespace Tests\Testing;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Database\Seeds\TagsSeeder;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Tag;
use Tests\TestCase;

class InteractsWithDatabaseTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

        Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
        Artisan::call('db:seed', ['--class' => TagsSeeder::class]);
    }

    public function testAssertDatabaseHas()
    {
        $this->assertDatabaseHas('characters', [
            "_key" => "NedStark",
            "name" => "Ned",
            "surname" => "Stark",
            "alive" => true,
            "age" => 41,
            "residence_id" => "locations/winterfell"
        ]);
    }

    public function testAssertDatabaseMissing()
    {
        $this->assertDatabaseMissing('characters', [
            "_key" => "NedStarkIsDead",
            "name" => "Ned",
            "surname" => "Stark",
            "alive" => true,
            "age" => 41,
            "residence_id" => "locations/winterfell"
        ]);
    }

    public function testAssertDatabaseCount()
    {
        $this->assertDatabaseCount('characters', 43);
    }

    public function testAssertDatabaseDeletedByModel()
    {
        $ned = Character::find('characters/NedStark');

        $ned->delete();

        $this->assertDeleted($ned);

        $this->expectException(ModelNotFoundException::class);
        Character::findOrFail('characters/NedStark');
    }

    public function testAssertDatabaseDeletedByData()
    {
        $ned = Character::find('characters/NedStark');

        $ned->delete();

        $this->assertDeleted('characters', [
            "_key" => "NedStarkIsDead",
            "name" => "Ned",
            "surname" => "Stark",
            "alive" => true,
            "age" => 41,
            "residence_id" => "locations/winterfell"
        ]);

        $this->expectException(ModelNotFoundException::class);
        Character::findOrFail('characters/NedStark');
    }

    public function testAssertSoftDeletedByModel()
    {
        $strong = Tag::find("tags/A");
        $strong->delete();
        $this->assertSoftDeleted($strong);
    }

    public function testAssertSoftDeletedByData()
    {
        $strong = Tag::find("tags/A");
        $strong->delete();
        $this->assertSoftDeleted('tags', [
            "_key" => "A",
            "en" => "strong",
            "de" => "stark"
        ]);
    }

    public function testAssertNotSoftDeletedByModel()
    {
        $strong = Tag::find("tags/A");
        $this->assertNotSoftDeleted($strong);
    }

    public function testAssertNotSoftDeletedByData()
    {
        $strong = Tag::find("tags/A");

        $this->assertNotSoftDeleted('tags', [
            "_key" => "A",
            "en" => "strong",
            "de" => "stark"
        ]);
    }

    public function testAssertModelExists()
    {
        $ned = Character::find('characters/NedStark');
        $this->assertModelExists($ned);
    }

    public function testAssertModelMissing()
    {
        $ned = Character::find('characters/NedStark');

        $ned->delete();

        $this->assertModelMissing($ned);
    }

    public function testCastAsJson()
    {
        $value = [
            'key' => 'value'
        ];
        $result = $this->castAsJson($value);

        $this->assertSame($value, $result);
    }
}
