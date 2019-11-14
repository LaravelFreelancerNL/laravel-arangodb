<?php

use ArangoDBClient\Document as ArangoDocument;
use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use Mockery as M;

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
        m::close();
        Carbon::setTestNow(null);
        Model::unsetEventDispatcher();
        Carbon::resetToStringFormat();
        M::close();
    }

    public function test()
    {
        $this->assertTrue(true);
    }

}

class EloquentModelStub extends Model
{
    public $connection;
    protected $table = 'stub';
    protected $guarded = [];
}
