<?php

namespace Tests\Query;

use LaravelFreelancerNL\Aranguent\Connection as Connection;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use Mockery as m;
use Tests\TestCase;

class WheresTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    protected function getBuilder()
    {
        $grammar = new Grammar();
        $processor = m::mock(Processor::class);

        return new Builder(m::mock(Connection::class), $grammar, $processor);
    }

    public function testBasicWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('_id', '=', 1);
        $this->assertSame('FOR userDoc IN users FILTER userDoc._id == @' . $builder->aqb->getQueryId() . '_1 RETURN userDoc', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testBasicWheresWithMultiplePredicates()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('_id', '=', 1)->where('email', '=', 'foo');
        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc._id == @' . $builder->aqb->getQueryId() . '_1 AND userDoc.email == @' . $builder->aqb->getQueryId() . '_2 RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testBasicOrWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->where('_id', '==', 1)->orWhere('email', '==', 'foo');
        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc._id == @' . $builder->aqb->getQueryId() . '_1 OR userDoc.email == @' . $builder->aqb->getQueryId() . '_2 RETURN userDoc',
            $builder->toSql()
        );
        $this->assertEquals([0 => 1, 1 => 'foo'], $builder->getBindings());
    }

    public function testWhereOperatorConversion()
    {
        $builder = $this->getBuilder();
        $builder->select('*')
            ->from('users')
            ->where('email', '=', 'email@example.com')
            ->where('_key', '<>', 'keystring');
        $this->assertSame(
            'FOR userDoc IN users '
            . 'FILTER userDoc.email == @' . $builder->aqb->getQueryId() . '_1 AND userDoc._key != @' . $builder->aqb->getQueryId() . '_2 RETURN userDoc',
            $builder->toSql()
        );
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
        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc._key == 1 OR userDoc._key == null RETURN userDoc',
            $builder->toSql()
        );
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
        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc._key > 1 OR userDoc._key != null RETURN userDoc',
            $builder->toSql()
        );
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereIn()
    {
        $builder = $this->getBuilder();

        $builder->select()
            ->from('users')
            ->where('country', 'IN', ['The Netherlands', 'Germany', 'Great-Britain']);
        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc.country IN [@'
            . $builder->aqb->getQueryId()
            . '_1,"Germany","Great-Britain"] RETURN userDoc',
            $builder->toSql()
        );
    }


}