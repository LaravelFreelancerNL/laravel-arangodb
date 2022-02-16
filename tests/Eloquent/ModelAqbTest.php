<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Models\Character;

test('model by aql with query builder', function () {
    $results = Character::fromAqb(
        DB::aqb()
            ->for('characterDoc', 'characters')
            ->return('characterDoc')
    );

    expect($results)->toBeInstanceOf(Collection::class);
    expect($results->first())->toBeInstanceOf(Character::class);
});

test('model by aql with closure', function () {
    $results = Character::fromAqb(
        function ($aqb) {
            return $aqb->for('characterDoc', 'characters')
                ->return('characterDoc');
        }
    );

    expect($results)->toBeInstanceOf(Collection::class);
    expect($results->first())->toBeInstanceOf(Character::class);
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
