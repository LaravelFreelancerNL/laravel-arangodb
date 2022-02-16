<?php

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    Character::insert(
        [
            [
                '_key'    => 'NedStark',
                'name'    => 'Ned',
                'surname' => 'Stark',
                'alive'   => false,
                'age'     => 41,
                'traits'  => ['A', 'H', 'C', 'N', 'P'],
            ],
            [
                '_key'    => 'RobertBaratheon',
                'name'    => 'Robert',
                'surname' => 'Baratheon',
                'alive'   => false,
                'age'     => null,
                'traits'  => ['A', 'H', 'C'],
            ],
        ]
    );
});

afterEach(function () {
    Carbon::setTestNow(null);
    Carbon::resetToStringFormat();

    Model::unsetEventDispatcher();

    M::close();
});

test('create aranguent model', function () {
    $this->artisan(
        'aranguent:model',
        [
            'name'    => 'AranguentModelTest',
            '--force' => '',
        ]
    )->run();

    $file = __DIR__ . '/../../vendor/orchestra/testbench-core/laravel/app/Models/AranguentModelTest.php';

    //assert file exists
    $this->assertFileExists($file);

    //assert file refers to Aranguent Base Model
    $content = file_get_contents($file);
    $this->assertStringContainsString('use LaravelFreelancerNL\Aranguent\Eloquent\Model;', $content);
});

test('update model', function () {
    $character = Character::first();
    $initialAge = $character->age;

    $character->update(['age' => ($character->age + 1)]);

    $fresh = $character->fresh();

    $this->assertSame(($initialAge + 1), $fresh->age);
});

test('update or create', function () {
    $character = Character::first();
    $initialAge = $character->age;
    $newAge = ($initialAge + 1);

    $character->updateOrCreate(['age' => $initialAge], ['age' => $newAge]);

    $fresh = $character->fresh();

    $this->assertSame($newAge, $fresh->age);
});

test('upsert', function () {
    $this->skipTestOnArangoVersionsBefore('3.7');

    Character::upsert(
        [
            [
               "id" => "NedStark",
               "name" => "Ned",
               "surname" => "Stark",
               "alive" => false,
               "age" => 41,
               "residence_id" => "winterfell"
            ],
            [
               "id" => "JaimeLannister",
               "name" => "Jaime",
               "surname" => "Lannister",
               "alive" => false,
               "age" => 36,
               "residence_id" => "the-red-keep"
            ],
        ],
        ['name', 'surname'],
        ['alive']
    );

    $ned = Character::find('NedStark');
    $jaime = Character::find('JaimeLannister');

    $this->assertFalse($ned->alive);
    $this->assertFalse($jaime->alive);
});

test('delete model', function () {
    $character = Character::first();

    $character->delete();

    $deletedCharacter = Character::first();

    $this->assertNotEquals($character->id, $deletedCharacter->id);
});

test('destroy model', function () {
    $id = 'NedStark';
    Character::destroy($id);

    $this->assertDatabaseMissing('characters', ['id' => $id]);
});

test('truncate model', function () {
    Character::truncate();

    $this->assertDatabaseCount('characters', 0);
});

test('count', function () {
    $result = Character::count();
    $this->assertEquals(2, $result);
});

test('max', function () {
    $result = Character::max('age');
    $this->assertEquals(41, $result);
});

test('min', function () {
    $result = Character::min('age');
    $this->assertEquals(41, $result);
});

test('average', function () {
    $result = Character::average('age');
    $this->assertEquals(41, $result);
});

test('sum', function () {
    $result = Character::sum('age');
    $this->assertEquals(41, $result);
});

test('get id', function () {
    $ned = Character::first();
    $this->assertEquals('NedStark', $ned->id);
});

test('set underscore id', function () {
    $ned = Character::first();
    $ned->_id = 'characters/NedStarkIsDead';

    $this->assertEquals('characters/NedStarkIsDead', $ned->_id);
    $this->assertEquals('NedStarkIsDead', $ned->id);
});

test('set id', function () {
    $ned = Character::first();
    $ned->id = 'NedStarkIsDead';

    $this->assertEquals('NedStarkIsDead', $ned->id);
    $this->assertEquals('characters/NedStarkIsDead', $ned->_id);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
