<?php

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Models\Character;

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
    expect($file)->toBeFile();

    //assert file refers to Aranguent Base Model
    $content = file_get_contents($file);
    expect($content)->toContain('use LaravelFreelancerNL\Aranguent\Eloquent\Model;');
});

test('update model', function () {
    $character = Character::first();
    $initialAge = $character->age;

    $character->update(['age' => ($character->age + 1)]);

    $fresh = $character->fresh();

    expect($fresh->age)->toBe(($initialAge + 1));
});

test('update or create', function () {
    $character = Character::first();
    $initialAge = $character->age;
    $newAge = ($initialAge + 1);

    $character->updateOrCreate(['age' => $initialAge], ['age' => $newAge]);

    $fresh = $character->fresh();

    expect($fresh->age)->toBe($newAge);
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

    expect($ned->alive)->toBeFalse();
    expect($jaime->alive)->toBeFalse();
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
    expect($result)->toEqual(2);
});

test('max', function () {
    $result = Character::max('age');
    expect($result)->toEqual(41);
});

test('min', function () {
    $result = Character::min('age');
    expect($result)->toEqual(41);
});

test('average', function () {
    $result = Character::average('age');
    expect($result)->toEqual(41);
});

test('sum', function () {
    $result = Character::sum('age');
    expect($result)->toEqual(41);
});

test('get id', function () {
    $ned = Character::first();
    expect($ned->id)->toEqual('NedStark');
});

test('set underscore id', function () {
    $ned = Character::first();
    $ned->_id = 'characters/NedStarkIsDead';

    expect($ned->_id)->toEqual('characters/NedStarkIsDead');
    expect($ned->id)->toEqual('NedStarkIsDead');
});

test('set id', function () {
    $ned = Character::first();
    $ned->id = 'NedStarkIsDead';

    expect($ned->id)->toEqual('NedStarkIsDead');
    expect($ned->_id)->toEqual('characters/NedStarkIsDead');
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
}
