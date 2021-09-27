<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Document;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\setup\Models\Character;
use Tests\TestCase;

class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow(null);
        Carbon::resetToStringFormat();
        Model::unsetEventDispatcher();
        M::close();
    }

    public function testCreateAranguentModel()
    {
        $this->artisan(
            'aranguent:model',
            [
                'name'    => 'Aranguent',
                '--force' => '',
            ]
        )->run();

        $file = __DIR__ . '/../../vendor/orchestra/testbench-core/laravel/app/Models/Aranguent.php';

        //assert file exists
        $this->assertFileExists($file);

        //assert file refers to Aranguent Base Model
        $content = file_get_contents($file);
        $this->assertStringContainsString('use LaravelFreelancerNL\Aranguent\Eloquent\Model;', $content);
    }

    public function testUpdateModel()
    {
        $character = Character::first();
        $initialAge = $character->age;

        $character->update(['age' => ($character->age + 1)]);

        $fresh = $character->fresh();

        $this->assertSame(($initialAge + 1), $fresh->age);
    }

    public function testDeleteModel()
    {
        $character = Character::first();

        $character->delete();

        $deletedCharacter = Character::firstOrFail();

        $this->assertNotEquals($character->_key, $deletedCharacter->_key);
    }

    public function testCount()
    {
        $result = Character::count();
        $this->assertEquals(2, $result);
    }

    public function testMax()
    {
        $result = Character::max('age');
        $this->assertEquals(41, $result);
    }

    public function testMin()
    {
        $result = Character::min('age');
        $this->assertEquals(41, $result);
    }

    public function testAverage()
    {
        $result = Character::average('age');
        $this->assertEquals(41, $result);
    }

    public function testSum()
    {
        $result = Character::sum('age');
        $this->assertEquals(41, $result);
    }

    public function testOrderByRandom()
    {
        $results = DB::table('characters')
            ->inRandomOrder()
            ->toSql();

        $this->assertEquals('FOR characterDoc IN characters SORT RAND()', $results);
    }

    public function testGetArangoId()
    {
        $ned = Character::first();
        $this->assertEquals('characters/NedStark', $ned->_id);
    }

    public function testGetId()
    {
        $ned = Character::first();
        $this->assertEquals('characters/NedStark', $ned->id);
    }

    public function testSetId()
    {
        $ned = Character::first();
        $ned->id = 'characters/NedStarkIsDead';

        $this->assertEquals('characters/NedStarkIsDead', $ned->_id);
        $this->assertEquals('characters/NedStarkIsDead', $ned->id);
    }

    public function testSetKey()
    {
        $ned = Character::first();
        $ned->_key = 'NedStarkIsDead';

        $this->assertEquals('NedStarkIsDead', $ned->_key);
        $this->assertEquals('characters/NedStarkIsDead', $ned->_id);
    }
}
