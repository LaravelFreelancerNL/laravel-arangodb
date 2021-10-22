<?php

namespace Tests\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;
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

    public function testExecuteReturnsCollectionOfModels()
    {
        $aqb = (new QueryBuilder())
            ->for('characterDoc', 'characters')
            ->return('characterDoc');
        $results = Character::execute($aqb);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertInstanceOf(Character::class, $results->first());
    }
}
