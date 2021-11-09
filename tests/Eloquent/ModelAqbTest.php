<?php

namespace Tests\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Tests\Setup\Models\Character;
use Tests\TestCase;

class ModelAqbTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

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

    public function testModelByAqlWithQueryBuilder()
    {
        $results = Character::fromAqb(
            \DB::aqb()
                ->for('characterDoc', 'characters')
                ->return('characterDoc')
        );

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertInstanceOf(Character::class, $results->first());
    }

    public function testModelByAqlWithClosure()
    {
        $results = Character::fromAqb(
            function ($aqb) {
                return $aqb->for('characterDoc', 'characters')
                    ->return('characterDoc');
            }
        );

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertInstanceOf(Character::class, $results->first());
    }
}
