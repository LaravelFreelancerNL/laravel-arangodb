<?php

namespace Tests\Testing;

use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            "id" => "NedStark",
            "name" => "Ned",
            "surname" => "Stark",
            "alive" => true,
            "age" => 41,
            "residence_id" => "winterfell"
        ]);
    }

    public function testAssertDatabaseMissing()
    {
        $this->assertDatabaseMissing('characters', [
            "id" => "NedStarkIsDead",
            "name" => "Ned",
            "surname" => "Stark",
            "alive" => true,
            "age" => 41,
            "residence_id" => "winterfell"
        ]);
    }

    public function testAssertDatabaseCount()
    {
        $this->assertDatabaseCount('characters', 43);
    }

    public function testAssertDatabaseDeletedByModel()
    {
        $ned = Character::find('NedStark');

        $ned->delete();

        $this->assertDeleted($ned);

        $this->expectException(ModelNotFoundException::class);
        Character::findOrFail('NedStark');
    }

    public function testAssertDatabaseDeletedByData()
    {
        $ned = Character::find('NedStark');

        $ned->delete();

        $this->assertDeleted('characters', [
            "id" => "NedStarkIsDead",
            "name" => "Ned",
            "surname" => "Stark",
            "alive" => true,
            "age" => 41,
            "residence_id" => "winterfell"
        ]);

        $this->expectException(ModelNotFoundException::class);
        Character::findOrFail('NedStark');
    }

    public function testAssertSoftDeletedByModel()
    {
        $strong = Tag::find("A");
        $strong->delete();
        $this->assertSoftDeleted($strong);
    }

    public function testAssertSoftDeletedByData()
    {
        $strong = Tag::find("A");
        $strong->delete();
        $this->assertSoftDeleted('tags', [
            "id" => "A",
            "en" => "strong",
            "de" => "stark"
        ]);
    }

    public function testAssertNotSoftDeletedByModel()
    {
        $strong = Tag::find("A");
        $this->assertNotSoftDeleted($strong);
    }

    public function testAssertNotSoftDeletedByData()
    {
        $strong = Tag::find("A");

        $this->assertNotSoftDeleted('tags', [
            "id" => "A",
            "en" => "strong",
            "de" => "stark"
        ]);
    }

    public function testAssertModelExists()
    {
        $ned = Character::find('NedStark');
        $this->assertModelExists($ned);
    }

    public function testAssertModelMissing()
    {
        $ned = Character::find('NedStark');

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
