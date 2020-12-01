<?php

namespace Tests\Query;

use LaravelFreelancerNL\Aranguent\Connection as Connection;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use Mockery as m;
use Tests\TestCase;

class AliasTest extends TestCase
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

    public function testExtractAlias()
    {
        $builder = $this->getBuilder();
        [$table, $alias] = $builder->grammar->extractAlias('users AS u');

        $this->assertEquals('users', $table);
        $this->assertEquals('u', $alias);
    }

    public function testExtractWrappedAlias()
    {
        $builder = $this->getBuilder();
        [$table, $alias] = $builder->grammar->extractAlias('users AS `a user`');

        $this->assertEquals('users', $table);
        $this->assertEquals('a user', $alias);
    }

    public function testGenerateTableAlias()
    {
        $builder = $this->getBuilder();
        $alias = $builder->grammar->generateTableAlias('users');

        $this->assertEquals('userDoc', $alias);
    }

    public function testRegisterTableAliasRegistration()
    {
        $builder = $this->getBuilder();
        $alias = $builder->grammar->registerTableAlias('users', 'userDoc');
        $result = $builder->getTableAlias('users');

        $this->assertEquals(  'userDoc', $result);
    }

    public function testRegisterColumnAliasRegistration()
    {
        $builder = $this->getBuilder();
        $alias = $builder->grammar->registerColumnAlias('column', 'pivot_column');
        $result = $builder->grammar->getColumnAlias('column');

        $this->assertEquals('pivot_column', $result);
    }

    public function testRegisterExtractedColumnAlias()
    {
        $builder = $this->getBuilder();
        $alias = $builder->grammar->registerColumnAlias('column As `pivot_column`');
        $result = $builder->grammar->getColumnAlias('column');

        $this->assertEquals('pivot_column', $result);

    }

}