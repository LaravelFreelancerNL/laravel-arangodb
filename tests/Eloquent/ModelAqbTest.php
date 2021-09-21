<?php

namespace Tests\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Models\Character;
use Tests\TestCase;

class ModelAqbTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
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
