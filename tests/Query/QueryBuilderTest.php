<?php

use Illuminate\Database\ConnectionInterface;
use Illuminate\Pagination\AbstractPaginator as Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelFreelancerNL\Aranguent\Connection as Connection;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as FluentAQL;
use Mockery as m;

class QueryBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    protected function getBuilder()
    {
        $grammar = new Grammar;
        $processor = m::mock(Processor::class);

        return new Builder(m::mock(Connection::class), $grammar, $processor);
    }

    public function testInsertGetIdMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('execute')->once()->with(FluentAQL::class)->andReturn(1);
        $result = $builder->from('users')->insertGetId(['email' => 'foo']);
        $this->assertEquals(1, $result);
    }

    public function testBasicSelect()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users');
        $this->assertSame('FOR userDoc IN users RETURN userDoc', $builder->toSql());

        $builder = $this->getBuilder();
        $builder->select(['name', 'email'])->from('users');
        $this->assertSame('FOR userDoc IN users RETURN {"name":userDoc.name,"email":userDoc.email}', $builder->toSql());
    }

    public function testBasicSelectWithGetColumns()
    {
        $builder = $this->getBuilder();
        $builder->getProcessor()->shouldReceive('processSelect');
        $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(function ($aqb) {
            $this->assertSame('FOR userDoc IN users RETURN userDoc', $aqb->toAql());
        });
        $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(function ($aqb) {
            $this->assertSame('FOR userDoc IN users RETURN {"name":userDoc.name,"email":userDoc.email}', $aqb->toAql());
        });
        $builder->getConnection()->shouldReceive('select')->once()->andReturnUsing(function ($aqb) {
            $this->assertSame('FOR userDoc IN users RETURN {"name":userDoc.name}', $aqb->toAql());
        });

        $builder->from('users')->get();
        $this->assertNull($builder->columns);

        $builder->from('users')->get(['name', 'email']);
        $this->assertNull($builder->columns);

        $builder->from('users')->get('name');
        $this->assertNull($builder->columns);

        $this->assertSame('FOR userDoc IN users', $builder->toSql());
        $this->assertNull($builder->columns);
    }

    public function testBasicWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('_id', '=', 1);
        $this->assertSame('FOR userDoc IN users FILTER userDoc._id == 1 RETURN userDoc', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testBasicWhereWithReferredValued()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('userDoc._id', '=', 1);
        $this->assertSame('FOR userDoc IN users FILTER userDoc._id == 1 RETURN userDoc', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testBasicWheresWithMultiplePredicates()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('_id', '=', 1)->where('email', '=', 'foo');
        $this->assertSame('FOR userDoc IN users FILTER userDoc._id == 1 AND userDoc.email == "foo" RETURN userDoc', $builder->toSql());
    }

    public function testBasicOrWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('_id', '==', 1)->orWhere('email', '==', 'foo');
        $this->assertSame('FOR userDoc IN users FILTER userDoc._id == 1 OR userDoc.email == "foo" RETURN userDoc', $builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $builder->getBindings());
    }

    public function testWhereOperatorConversion()
    {
        $builder = $this->getBuilder();
        $builder->select('*')
            ->from('users')
            ->where('email', '=', 'email@example.com')
            ->where('_key', '<>', 'keystring');
        $this->assertSame('FOR userDoc IN users FILTER userDoc.email == "email@example.com" AND userDoc._key != "keystring" RETURN userDoc', $builder->toSql());
        $this->assertEquals([0 => 'email@example.com', 1 => 'keystring'], $builder->getBindings());
    }

    public function testBasicWhereNulls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNull('_key');
        $this->assertSame('FOR userDoc IN users FILTER userDoc._key == null RETURN userDoc', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('_key', '=', 1)->orWhereNull('_key');
        $this->assertSame('FOR userDoc IN users FILTER userDoc._key == 1 OR userDoc._key == null RETURN userDoc', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testBasicWhereNotNulls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotNull('_key');
        $this->assertSame('FOR userDoc IN users FILTER userDoc._key != null RETURN userDoc', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('_key', '>', 1)->orWhereNotNull('_key');
        $this->assertSame('FOR userDoc IN users FILTER userDoc._key > 1 OR userDoc._key != null RETURN userDoc', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testOrderBys()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderBy('email')->orderBy('age', 'desc');
        $this->assertSame('FOR userDoc IN users SORT userDoc.email asc, userDoc.age desc RETURN userDoc', $builder->toSql());
    }

    public function testLimitsAndOffsets()
    {
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
    }

    public function testUpdateMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('update')->once()->with(FluentAQL::class)->andReturn(1);
        $result = $builder->from('users')->where('userDoc._id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testDeleteMethod()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with(FluentAQL::class)->andReturn(1);
        $result = $builder->from('users')->where('userDoc.email', '=', 'foo')->delete();
        $this->assertEquals(1, $result);

        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('delete')->once()->with(FluentAQL::class)->andReturn(1);
        $result = $builder->from('users')->delete(1);
        $this->assertEquals(1, $result);
    }

    public function testAggregates()
    {
        $builder = $this->getBuilder();
        $builder->getConnection()->shouldReceive('select')->once()->with(FluentAQL::class)->andReturn([['aggregate' => 1]]);

        $results = $builder->from('users')->count();
        $this->assertEquals(1, $results);
    }

    public function testPaginate()
    {
        $perPage = 16;
        $columns = ['test'];
        $pageName = 'page-name';
        $page = 1;
        $builder = $this->getMockQueryBuilder();
        $path = 'http://foo.bar?page=3';

        $results = collect([['test' => 'foo'], ['test' => 'bar']]);

        $builder->shouldReceive('getCountForPagination')->once()->andReturn(2);
        $builder->shouldReceive('forPage')->once()->with($page, $perPage)->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($results);

        Paginator::currentPathResolver(function () use ($path) {
            return $path;
        });

        $result = $builder->paginate($perPage, $columns, $pageName, $page);

        $this->assertEquals(new LengthAwarePaginator($results, 2, $perPage, $page, [
            'path' => $path,
            'pageName' => $pageName,
        ]), $result);
    }

    /**
     * @return m\MockInterface
     */
    protected function getMockQueryBuilder()
    {
        return m::mock(Builder::class, [
            m::mock(ConnectionInterface::class),
            new Grammar,
            m::mock(Processor::class),
        ])->makePartial();
    }
}
