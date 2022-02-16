<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Models\Character;
use Tests\TestCase;

uses(TestCase::class);

test('model by aql with query builder', function () {
    $results = Character::fromAqb(
        DB::aqb()
            ->for('characterDoc', 'characters')
            ->return('characterDoc')
    );

    $this->assertInstanceOf(Collection::class, $results);
    $this->assertInstanceOf(Character::class, $results->first());
});

test('model by aql with closure', function () {
    $results = Character::fromAqb(
        function ($aqb) {
            return $aqb->for('characterDoc', 'characters')
                ->return('characterDoc');
        }
    );

    $this->assertInstanceOf(Collection::class, $results);
    $this->assertInstanceOf(Character::class, $results->first());
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Character::insert(
        [
            [
                '_key'         => 'NedStark',
                'name'         => 'Ned',
                'surname'      => 'Stark',
                'alive'        => false,
                'age'          => 41,
                'traits'       => ['A', 'H', 'C', 'N', 'P'],
                'location_id' => 'locations/kingslanding',
            ],
        ]
    );
}
