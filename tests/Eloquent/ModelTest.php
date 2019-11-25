<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Eloquent\Builder;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Query\Builder as QueryBuilder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use LaravelFreelancerNL\Aranguent\Tests\models\Character as Character;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use Mockery as M;

class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        Character::insert([
            [
                '_key' => 'NedStark',
                'name' => 'Ned',
                'surname' => 'Stark',
                'alive' => false,
                'age' => 41,
                'traits' => ['A', 'H', 'C', 'N', 'P'],
            ],
            [
                '_key' => 'RobertBaratheon',
                'name' => 'Robert',
                'surname' => 'Baratheon',
                'alive' => false,
                'age' => null,
                'traits' => ['A', 'H', 'C'],
            ],
        ]);
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
            '--force' => '',
        ])->run();

        $file = __DIR__.'/../../vendor/orchestra/testbench-core/laravel/app/Aranguent.php';

        //assert file exists
        $this->assertFileExists($file);

        //assert file refers to Aranguent Base Model
        $content = file_get_contents($file);
        $this->assertStringContainsString('use LaravelFreelancerNL\Aranguent\Eloquent\Model;', $content);
    }

    public function testInsertModel()
    {
        $characters = Character::all();
        $this->assertCount(2, $characters);
    }

    public function testUpdateModel()
    {
        $characters = Character::all();
        foreach ($characters as $character) {
            $results[] = $character->update(['age' => ($character->age + 1)]);
        }

        $characters = Character::all();
        $this->assertCount(2, $characters);
    }

    public function testDeleteModel()
    {
        $character = Character::first();

        $character->delete();
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
            ->get();

        $this->assertCount(2, $results);
    }

    public function testCastDocumentObjectToArrayWithoutBinaryStrings()
    {
        $data = [
            '_key' => 'NedStark',
            'name' => 'Ned',
            'surname' => 'Stark',
            'alive' => false,
            'age' => 41,
            'traits' => ['A', 'H', 'C', 'N', 'P'],
        ];
        $doc = new \LaravelFreelancerNL\Aranguent\Document();
        $doc = $doc->createFromArray($data);

        $this->assertEquals((array) $doc, $data);
    }

    public function testQualifyColumn()
    {
        $model = new EloquentModelStub;

        $this->assertSame('stubDoc.column', $model->qualifyColumn('column'));
    }
}

class EloquentModelStub extends Model
{
    public $connection;
    public $scopesCalled = [];
    protected $table = 'stub';
    protected $guarded = [];
    protected $morph_to_stub_type = EloquentModelSaveStub::class;

    public function getListItemsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setListItemsAttribute($value)
    {
        $this->attributes['list_items'] = json_encode($value);
    }

    public function getPasswordAttribute()
    {
        return '******';
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = sha1($value);
    }

    public function publicIncrement($column, $amount = 1, $extra = [])
    {
        return $this->increment($column, $amount, $extra);
    }

    public function belongsToStub()
    {
        return $this->belongsTo(EloquentModelSaveStub::class);
    }

    public function morphToStub()
    {
        return $this->morphTo();
    }

    public function morphToStubWithKeys()
    {
        return $this->morphTo(null, 'type', 'id');
    }

    public function morphToStubWithName()
    {
        return $this->morphTo('someName');
    }

    public function morphToStubWithNameAndKeys()
    {
        return $this->morphTo('someName', 'type', 'id');
    }

    public function belongsToExplicitKeyStub()
    {
        return $this->belongsTo(EloquentModelSaveStub::class, 'foo');
    }

    public function incorrectRelationStub()
    {
        return 'foo';
    }

    public function getDates()
    {
        return [];
    }

    public function getAppendableAttribute()
    {
        return 'appended';
    }

    public function scopePublished(Builder $builder)
    {
        $this->scopesCalled[] = 'published';
    }

    public function scopeCategory(Builder $builder, $category)
    {
        $this->scopesCalled['category'] = $category;
    }

    public function scopeFramework(Builder $builder, $framework, $version)
    {
        $this->scopesCalled['framework'] = [$framework, $version];
    }
}

class EloquentModelSaveStub extends Model
{
    protected $table = 'save_stub';
    protected $guarded = ['id'];

    public function save(array $options = [])
    {
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        $_SERVER['__eloquent.saved'] = true;

        $this->fireModelEvent('saved', false);
    }

    public function setIncrementing($value)
    {
        $this->incrementing = $value;
    }

    public function getConnection()
    {
        $mock = m::mock(Connection::class);
        $mock->shouldReceive('getQueryGrammar')->andReturn($grammar = m::mock(Grammar::class));
        $mock->shouldReceive('getPostProcessor')->andReturn($processor = m::mock(Processor::class));
        $mock->shouldReceive('getName')->andReturn('name');
        $mock->shouldReceive('query')->andReturnUsing(function () use ($mock, $grammar, $processor) {
            return new QueryBuilder($mock, $grammar, $processor);
        });

        return $mock;
    }
}
