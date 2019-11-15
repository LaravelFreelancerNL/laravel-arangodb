<?php

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use Mockery as M;
use test\models\Character as Character;

class ModelTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());
    }

    public function tearDown() : void
    {
        parent::tearDown();
        Carbon::setTestNow(null);
        Carbon::resetToStringFormat();
        Model::unsetEventDispatcher();
        M::close();
    }

    public function testCreateAranguentModel()
    {
        $this->artisan('aranguent:model', [
            'name' => 'Aranguent',
            '--force' => ''
        ])->run();

        $file = __DIR__.'/../../vendor/orchestra/testbench-core/laravel/app/Aranguent.php';

        //assert file exists
        $this->assertFileExists($file);

        //assert file refers to Aranguent Base Model
        $content = file_get_contents($file);
        $this->assertStringContainsString('use LaravelFreelancerNL\Aranguent\Eloquent\Model;', $content);
    }

    public function testCanInsertModel()
    {

        $character = Character::insert([
            [
                '_key' => 'NedStark',
                'name' => 'Ned',
                'surname' => 'Stark',
                'alive' => false,
                'age' => 41,
                'traits' => [
                    "A","H","C","N","P"
                ],
            ],
            [
                "_key" => "RobertBaratheon", "name" => "Robert", "surname" => "Baratheon", "alive" => false, "traits" => ["A","H","C"]
            ]
        ]);
    }

    public function testCanGetAllModels()
    {
        $insertedCharacter = Character::insert([
            [
                '_key' => 'NedStark',
                'name' => 'Ned',
                'surname' => 'Stark',
                'alive' => false,
                'age' => 41,
                'traits' => [
                    "A","H","C","N","P"
                ],
            ],
            [
                "_key" => "RobertBaratheon", "name" => "Robert", "surname" => "Baratheon", "alive" => false, "traits" => ["A","H","C"]
            ]
        ]);

        $selectedCharacters = Character::all();

        $this->assertCount(2, $selectedCharacters);
        foreach ($selectedCharacters as $char) {
            $this->assertInstanceOf(Character::class, $char);
        }
    }
}

class EloquentModelStub extends Model
{
    public $connection;
    protected $table = 'stub';
    protected $guarded = [];
}
