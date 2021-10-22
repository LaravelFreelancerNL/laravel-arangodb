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
        parent::tearDown();

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
        $builder = $builder->select('*')->from('users')->where('_id', '=', 1);

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc._id == @'
              . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );

        $this->assertEquals([$builder->aqb->getQueryId() . '_1' => 1], $builder->getBindings());
    }

    public function testBasicWheresWithMultiplePredicates()
    {
        $builder = $this->getBuilder();
        $builder->select('*')
            ->from('users')
            ->where('_id', '=', 1)
            ->where('email', '=', 'foo');

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc._id == @'
            . $builder->aqb->getQueryId()
            . '_1 AND userDoc.email == @'
            . $builder->aqb->getQueryId()
            . '_2 RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testBasicOrWheres()
    {
        $builder = $this->getBuilder();
        $builder->select('*')
            ->from('users')
            ->where('_id', '==', 1)
            ->orWhere('email', '==', 'foo');

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc._id == @'
            . $builder->aqb->getQueryId() . '_1 OR userDoc.email == @'
            . $builder->aqb->getQueryId() . '_2 RETURN userDoc',
            $builder->toSql()
        );
        $this->assertEquals(
            [
                $builder->aqb->getQueryId() . '_1' => 1,
                $builder->aqb->getQueryId() . '_2' => 'foo'
            ],
            $builder->getBindings()
        );
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
            . 'FILTER userDoc.email == @'
            . $builder->aqb->getQueryId()
            . '_1 AND userDoc._key != @'
            . $builder->aqb->getQueryId()
            . '_2 RETURN userDoc',
            $builder->toSql()
        );
        $this->assertEquals(
            [
                $builder->aqb->getQueryId() . '_1' => 'email@example.com',
                $builder->aqb->getQueryId() . '_2' => 'keystring'
            ],
            $builder->getBindings()
        );
    }

    public function testWhereJsonArrowConversion()
    {
        $builder = $this->getBuilder();
        $builder->select('*')
            ->from('users')
            ->where('email->address', '=', 'email@example.com')
            ->where('profile->address->street', '!=', 'keystring');

        $this->assertSame(
            'FOR userDoc IN users '
            . 'FILTER userDoc.email.address == @'
            . $builder->aqb->getQueryId()
            . '_1 AND userDoc.profile.address.street != @'
            . $builder->aqb->getQueryId()
            . '_2 RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereJsonContains()
    {
        $builder = $this->getBuilder();
        $builder->select('*')
            ->from('users')
            ->whereJsonContains('options->languages', 'en');

        $this->assertSame(
            'FOR userDoc IN users '
            . 'FILTER @'
            . $builder->aqb->getQueryId()
            . '_1 IN userDoc.options.languages RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereJsonLength()
    {
        $builder = $this->getBuilder();
        $builder->select('*')
            ->from('users')
            ->whereJsonLength('options->languages', '>', 'en');

        $this->assertSame(
            'FOR userDoc IN users '
            . 'FILTER LENGTH(userDoc.options.languages) > @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereBetween()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereBetween('votes', [1, 100]);

        $this->assertSame(
            'FOR userDoc IN users FILTER (userDoc.votes >= @'
            . $builder->aqb->getQueryId()
            . '_1 AND userDoc.votes <= @'
            . $builder->aqb->getQueryId()
            . '_2) RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereNotBetween()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotBetween('votes', [1, 100]);

        $this->assertSame(
            'FOR userDoc IN users FILTER (userDoc.votes < @'
            . $builder->aqb->getQueryId()
            . '_1 OR userDoc.votes > @'
            . $builder->aqb->getQueryId()
            . '_2) RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereBetweenColumns()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereBetweenColumns('votes', ['min_vote', 'max_vote']);

        $this->assertSame(
            'FOR userDoc IN users FILTER (userDoc.votes >= userDoc.min_vote AND userDoc.votes <= userDoc.max_vote)'
            . ' RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereColumn()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn('first_name', '=', 'last_name');

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc.first_name == userDoc.last_name RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereColumnWithoutOperator()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereColumn('first_name', 'last_name');

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc.first_name == userDoc.last_name RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereNulls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNull('_key');
        $this->assertSame('FOR userDoc IN users FILTER userDoc._key == null RETURN userDoc', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')
            ->from('users')
            ->where('_key', '=', 1)
            ->orWhereNull('_key');

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc._key == @'
            . $builder->aqb->getQueryId()
            . '_1 OR userDoc._key == null RETURN userDoc',
            $builder->toSql()
        );
        $this->assertEquals([$builder->aqb->getQueryId() . '_1' => 1], $builder->getBindings());
    }

    public function testWhereNotNulls()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereNotNull('_key');
        $this->assertSame('FOR userDoc IN users FILTER userDoc._key != null RETURN userDoc', $builder->toSql());
        $this->assertEquals([], $builder->getBindings());

        $builder = $this->getBuilder();
        $builder->select('*')
            ->from('users')
            ->where('_key', '>', 1)
            ->orWhereNotNull('_key');

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc._key > @'
            . $builder->aqb->getQueryId()
            . '_1 OR userDoc._key != null RETURN userDoc',
            $builder->toSql()
        );
        $this->assertEquals([$builder->aqb->getQueryId() . '_1' => 1], $builder->getBindings());
    }

    public function testWhereIn()
    {
        $builder = $this->getBuilder();

        $builder->select()
            ->from('users')
            ->whereIn('country', ['The Netherlands', 'Germany', 'Great-Britain']);

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc.country IN @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereIntegerInRaw()
    {
        $builder = $this->getBuilder();

        $builder->select()
            ->from('users')
            ->whereIntegerInRaw('country', [0, 1, 2, 3]);

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc.country IN @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereNotIn()
    {
        $builder = $this->getBuilder();

        $builder->select()
            ->from('users')
            ->whereNotIn('country', ['The Netherlands', 'Germany', 'Great-Britain']);

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc.country NOT IN @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereIntegerNotInRaw()
    {
        $builder = $this->getBuilder();

        $builder->select()
            ->from('users')
            ->whereIntegerNotInRaw('country', [0, 1, 2, 3]);

        $this->assertSame(
            'FOR userDoc IN users FILTER userDoc.country NOT IN @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );
    }

    public function testWhereDate()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '2016-12-31');

        $this->assertSame(
            'FOR userDoc IN users FILTER DATE_FORMAT(userDoc.created_at, @'
            . $builder->aqb->getQueryId()
            . '_2) == @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );
        $this->assertEquals([
                $builder->aqb->getQueryId() . '_1' => '2016-12-31',
                $builder->aqb->getQueryId() . '_2' => "%yyyy-%mm-%dd"
            ], $builder->getBindings());
    }

    public function testWhereYear()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '2016');

        $this->assertSame(
            'FOR userDoc IN users FILTER DATE_YEAR(userDoc.created_at) == @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );

        $this->assertEquals([$builder->aqb->getQueryId() . '_1' => "2016"], $builder->getBindings());
    }

    public function testWhereMonth()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '12');

        $this->assertSame(
            'FOR userDoc IN users FILTER DATE_MONTH(userDoc.created_at) == @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );

        $this->assertEquals([$builder->aqb->getQueryId() . '_1' => "12"], $builder->getBindings());
    }

    public function testWhereDay()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '31');

        $this->assertSame(
            'FOR userDoc IN users FILTER DATE_DAY(userDoc.created_at) == @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );

        $this->assertEquals([$builder->aqb->getQueryId() . '_1' => "31"], $builder->getBindings());
    }

    public function testWhereTime()
    {
        $builder = $this->getBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '11:20:45');

        $this->assertSame(
            'FOR userDoc IN users FILTER DATE_FORMAT(userDoc.created_at, @'
            . $builder->aqb->getQueryId()
            . '_2) == @'
            . $builder->aqb->getQueryId()
            . '_1 RETURN userDoc',
            $builder->toSql()
        );

        $this->assertEquals([
                $builder->aqb->getQueryId() . '_1' => "11:20:45",
                $builder->aqb->getQueryId() . '_2' => "%hh:%ii:%ss"
            ], $builder->getBindings());
    }

    public function testWhereNested()
    {
        $builder = $this->getBuilder();

        $query = $builder->select('*')
            ->from('characters')
            ->where('surname', '==', 'Lannister')
            ->where(function ($query) {
                $query->where('age', '>', 20)
                    ->orWhere('alive', '=', true);
            })->toSql();

        $bindKeys = array_keys($builder->aqb->binds);

        $this->assertSame(
            'FOR characterDoc IN characters FILTER characterDoc.surname == @' . $bindKeys[0]
            . ' AND (characterDoc.age > @'
            . $bindKeys[1]
            . ' OR characterDoc.alive == @'
            . $bindKeys[2]
            . ') RETURN characterDoc',
            $query
        );
    }
}
