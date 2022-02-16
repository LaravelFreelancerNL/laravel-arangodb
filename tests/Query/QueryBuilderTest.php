<?php

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as FluentAQL;
use Mockery as m;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());
});

afterEach(function () {
    m::close();
});

test('insert get id', function () {
    $builder = $this->getBuilder();
    $builder->getConnection()->shouldReceive('execute')->once()->with(FluentAQL::class)->andReturn(1);
    $result = $builder->from('users')->insertGetId(['email' => 'foo']);
    $this->assertEquals(1, $result);
});

test('insert or ignore inserts data', function () {
    $characterData = [
        "_key" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "residence_id" => "winterfell"
    ];

    DB::table('characters')->insertOrIgnore($characterData);

    $result = DB::table('characters')
        ->where("name", "==", "Lyanna")
        ->count();

    $this->assertSame(1, $result);
});

test('insert or ignore doesnt error on duplicates', function () {
    $characterData = [
        "_key" => "LyannaStark",
        "name" => "Lyanna",
        "surname" => "Stark",
        "alive" => false,
        "age" => 25,
        "residence_id" => "winterfell"
    ];
    DB::table('characters')->insert($characterData);

    DB::table('characters')->insertOrIgnore($characterData);

    $result = DB::table('characters')
        ->where("name", "==", "Lyanna")
        ->count();

    $this->assertSame(1, $result);
});

test('basic select', function () {
    $builder = $this->getBuilder();
    $builder->select('*')->from('users');
    $this->assertSame('FOR userDoc IN users RETURN userDoc', $builder->toSql());

    $builder = $this->getBuilder();
    $builder->select(['name', 'email'])->from('users');
    $this->assertSame('FOR userDoc IN users RETURN {"name":userDoc.name,"email":userDoc.email}', $builder->toSql());
});

test('basic select with get columns', function () {
    $builder = $this->getBuilder();
    $builder->getProcessor()->shouldReceive('processSelect');
    $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(
        function ($aqb) {
            $this->assertSame('FOR userDoc IN users RETURN userDoc', $aqb->toAql());
        }
    );
    $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(
        function ($aqb) {
            $this->assertSame(
                'FOR userDoc IN users RETURN {"name":userDoc.name,"email":userDoc.email}',
                $aqb->toAql()
            );
        }
    );
    $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(
        function ($aqb) {
            $this->assertSame('FOR userDoc IN users RETURN userDoc.name', $aqb->toAql());
        }
    );

    $builder->from('users')->get();
    $this->assertNull($builder->columns);

    $builder->from('users')->get(['name', 'email']);
    $this->assertNull($builder->columns);

    $builder->from('users')->get('name');
    $this->assertNull($builder->columns);

    $this->assertSame('FOR userDoc IN users', $builder->toSql());
    $this->assertNull($builder->columns);
});

test('basic select with get one column', function () {
    $builder = $this->getBuilder();
    $builder->getProcessor()->shouldReceive('processSelect');
    $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(
        function ($aqb) {
            $this->assertSame('FOR userDoc IN users RETURN userDoc.name', $aqb->toAql());
        }
    );

    $builder->from('users')->get('name');
    $this->assertNull($builder->columns);
});

test('order bys', function () {
    $builder = $this->getBuilder();
    $builder->select('*')->from('users')->orderBy('email')->orderBy('age', 'desc');
    $this->assertSame(
        'FOR userDoc IN users SORT userDoc.email asc, userDoc.age desc RETURN userDoc',
        $builder->toSql()
    );
});

test('order by random', function () {
    $results = DB::table('characters')
        ->inRandomOrder()
        ->toSql();

    $this->assertEquals('FOR characterDoc IN characters SORT RAND()', $results);
});

test('limits and offsets', function () {
    $builder = $this->getBuilder();
    $builder->select('*')->from('users')->offset(5)->limit(10);
    $this->assertSame('FOR userDoc IN users LIMIT 5, 10 RETURN userDoc', $builder->toSql());

    $builder = $this->getBuilder();
    $builder->select('*')->from('users')->skip(5)->take(10);
    $this->assertSame('FOR userDoc IN users LIMIT 5, 10 RETURN userDoc', $builder->toSql());

    $builder = $this->getBuilder();
    $builder->select('*')->from('users')->skip(0)->take(0);
    $this->assertSame('FOR userDoc IN users LIMIT 0, 0 RETURN userDoc', $builder->toSql());

    $builder = $this->getBuilder();
    $builder->select('*')->from('users')->skip(-5)->take(-10);
    $this->assertSame('FOR userDoc IN users RETURN userDoc', $builder->toSql());
});

test('update method', function () {
    $builder = $this->getBuilder();
    $builder->getConnection()->shouldReceive('update')->once()->with(FluentAQL::class)->andReturn(1);
    $result = $builder->from('users')->where('userDoc._id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
    $this->assertEquals(1, $result);
});

test('delete method', function () {
    $builder = $this->getBuilder();
    $builder->getConnection()->shouldReceive('delete')->once()->with(FluentAQL::class)->andReturn(1);
    $result = $builder->from('users')->where('userDoc.email', '=', 'foo')->delete();
    $this->assertEquals(1, $result);

    $builder = $this->getBuilder();
    $builder->getConnection()->shouldReceive('delete')->once()->with(FluentAQL::class)->andReturn(1);
    $result = $builder->from('users')->delete(1);
    $this->assertEquals(1, $result);
});

test('first method', function () {
    $result = \DB::table('characters')->where('characterDoc.id', '=', 'NedStark')->first();

    $this->assertSame('NedStark', $result->id);
});

test('aggregates', function () {
    $results = DB::table('characters')->count();
    $this->assertEquals(43, $results);
});

test('paginate', function () {
    $result = DB::table('characters')->paginate(15)->toArray();
    $this->assertEquals(43, $result['total']);
    $this->assertEquals(15, count($result['data']));
});

test('paginate with filters', function () {
    $result = DB::table('characters')
        ->where('residence_id', 'winterfell')
        ->paginate(5)
        ->toArray();
    $this->assertEquals(15, $result['total']);
    $this->assertEquals(5, count($result['data']));
});

test('paginate with optional filters', function () {
    $residenceId = 'winterfell';
    $result = DB::table('characters')
        ->when(
            $residenceId,
            function ($query) use ($residenceId) {
                return $query->where('residence_id', '==', $residenceId)
                    ->orWhere('residence_id', '==', $residenceId);
            }
        )
        ->paginate(5)
        ->toArray();

    $this->assertEquals(15, $result['total']);
    $this->assertEquals(5, count($result['data']));
});

test('pluck', function () {
    $results = DB::table('characters')->pluck('name', 'id');

    $this->assertEquals(43, $results->count());
    $this->assertEquals('Ned', $results['NedStark']);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
}

/**
     * @return m\MockInterface
     */
function getMockQueryBuilder()
{
    return m::mock(
        Builder::class,
        [
            m::mock(ConnectionInterface::class),
            new Grammar(),
            m::mock(Processor::class),
        ]
    )->makePartial();
}
