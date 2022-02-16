<?php

use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as FluentAQL;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('insert get id', function () {
    $builder = getBuilder();
    $builder->getConnection()->shouldReceive('execute')->once()->with(FluentAQL::class)->andReturn(1);
    $result = $builder->from('users')->insertGetId(['email' => 'foo']);
    expect($result)->toEqual(1);
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

    expect($result)->toBe(1);
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

    expect($result)->toBe(1);
});

test('basic select', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users');
    expect($builder->toSql())->toBe('FOR userDoc IN users RETURN userDoc');

    $builder = getBuilder();
    $builder->select(['name', 'email'])->from('users');
    expect($builder->toSql())->toBe('FOR userDoc IN users RETURN {"name":userDoc.name,"email":userDoc.email}');
});

test('basic select with get columns', function () {
    $builder = getBuilder();
    $builder->getProcessor()->shouldReceive('processSelect');
    $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(
        function ($aqb) {
            expect($aqb->toAql())->toBe('FOR userDoc IN users RETURN userDoc');
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
            expect($aqb->toAql())->toBe('FOR userDoc IN users RETURN userDoc.name');
        }
    );

    $builder->from('users')->get();
    expect($builder->columns)->toBeNull();

    $builder->from('users')->get(['name', 'email']);
    expect($builder->columns)->toBeNull();

    $builder->from('users')->get('name');
    expect($builder->columns)->toBeNull();

    expect($builder->toSql())->toBe('FOR userDoc IN users');
    expect($builder->columns)->toBeNull();
});

test('basic select with get one column', function () {
    $builder = getBuilder();
    $builder->getProcessor()->shouldReceive('processSelect');
    $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(
        function ($aqb) {
            expect($aqb->toAql())->toBe('FOR userDoc IN users RETURN userDoc.name');
        }
    );

    $builder->from('users')->get('name');
    expect($builder->columns)->toBeNull();
});

test('order bys', function () {
    $builder = getBuilder();
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

    expect($results)->toEqual('FOR characterDoc IN characters SORT RAND()');
});

test('limits and offsets', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->offset(5)->limit(10);
    expect($builder->toSql())->toBe('FOR userDoc IN users LIMIT 5, 10 RETURN userDoc');

    $builder = getBuilder();
    $builder->select('*')->from('users')->skip(5)->take(10);
    expect($builder->toSql())->toBe('FOR userDoc IN users LIMIT 5, 10 RETURN userDoc');

    $builder = getBuilder();
    $builder->select('*')->from('users')->skip(0)->take(0);
    expect($builder->toSql())->toBe('FOR userDoc IN users LIMIT 0, 0 RETURN userDoc');

    $builder = getBuilder();
    $builder->select('*')->from('users')->skip(-5)->take(-10);
    expect($builder->toSql())->toBe('FOR userDoc IN users RETURN userDoc');
});

test('update method', function () {
    $builder = getBuilder();
    $builder->getConnection()->shouldReceive('update')->once()->with(FluentAQL::class)->andReturn(1);
    $result = $builder->from('users')->where('userDoc._id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
    expect($result)->toEqual(1);
});

test('delete method', function () {
    $builder = getBuilder();
    $builder->getConnection()->shouldReceive('delete')->once()->with(FluentAQL::class)->andReturn(1);
    $result = $builder->from('users')->where('userDoc.email', '=', 'foo')->delete();
    expect($result)->toEqual(1);

    $builder = getBuilder();
    $builder->getConnection()->shouldReceive('delete')->once()->with(FluentAQL::class)->andReturn(1);
    $result = $builder->from('users')->delete(1);
    expect($result)->toEqual(1);
});

test('first method', function () {
    $result = \DB::table('characters')->where('characterDoc.id', '=', 'NedStark')->first();

    expect($result->id)->toBe('NedStark');
});

test('aggregates', function () {
    $results = DB::table('characters')->count();
    expect($results)->toEqual(43);
});

test('paginate', function () {
    $result = DB::table('characters')->paginate(15)->toArray();
    expect($result['total'])->toEqual(43);
    expect(count($result['data']))->toEqual(15);
});

test('paginate with filters', function () {
    $result = DB::table('characters')
        ->where('residence_id', 'winterfell')
        ->paginate(5)
        ->toArray();
    expect($result['total'])->toEqual(15);
    expect(count($result['data']))->toEqual(5);
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

    expect($result['total'])->toEqual(15);
    expect(count($result['data']))->toEqual(5);
});

test('pluck', function () {
    $results = DB::table('characters')->pluck('name', 'id');

    expect($results->count())->toEqual(43);
    expect($results['NedStark'])->toEqual('Ned');
});
