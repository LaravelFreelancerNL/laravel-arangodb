<?php

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
        $builder->select('*')->from('users')->where('userDoc._id', '=', 1);
        $this->assertSame('FOR userDoc IN users FILTER userDoc._id == 1 RETURN userDoc', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testBasicWheresWithMultiplePredicates()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('userDoc._id', '=', 1)->where('userDoc.email', '=', 'foo');
        $this->assertSame('FOR userDoc IN users FILTER userDoc._id == 1 AND userDoc.email == "foo" RETURN userDoc', $builder->toSql());
    }

    public function testBasicOrWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('userDoc._id', '==', 1)->orWhere('userDoc.email', '==', 'foo');
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

    /**
     * Compile an aggregated select clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(\Illuminate\Database\Query\Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if (is_array($query->distinct)) {
            $column = 'distinct '.$this->columnize($query->distinct);
        } elseif ($query->distinct && $column !== '*') {
            $column = 'distinct '.$column;
        }

        return 'select '.$aggregate['function'].'('.$column.') as aggregate';
    }


    public function testOrderBys()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->orderBy('userDoc.email')->orderBy('userDoc.age', 'desc');
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
}