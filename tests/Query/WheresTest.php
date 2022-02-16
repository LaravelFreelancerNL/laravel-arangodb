<?php

use LaravelFreelancerNL\Aranguent\Connection as Connection;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use Mockery as m;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    m::close();
});

test('basic wheres', function () {
    $builder = getBuilder();
    $builder = $builder->select('*')->from('users')->where('id', '=', 1);

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc._key == @'
          . $builder->aqb->getQueryId()
        . '_1 RETURN userDoc',
        $builder->toSql()
    );

    $this->assertEquals([$builder->aqb->getQueryId() . '_1' => 1], $builder->getBindings());
});

test('basic wheres with multiple predicates', function () {
    $builder = getBuilder();
    $builder->select('*')
        ->from('users')
        ->where('id', '=', 1)
        ->where('email', '=', 'foo');

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc._key == @'
        . $builder->aqb->getQueryId()
        . '_1 AND userDoc.email == @'
        . $builder->aqb->getQueryId()
        . '_2 RETURN userDoc',
        $builder->toSql()
    );
});

test('basic or wheres', function () {
    $builder = getBuilder();
    $builder->select('*')
        ->from('users')
        ->where('id', '==', 1)
        ->orWhere('email', '==', 'foo');

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc._key == @'
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
});

test('where operator conversion', function () {
    $builder = getBuilder();
    $builder->select('*')
        ->from('users')
        ->where('email', '=', 'email@example.com')
        ->where('id', '<>', 'keystring');

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
});

test('where json arrow conversion', function () {
    $builder = getBuilder();
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
});

test('where json contains', function () {
    $builder = getBuilder();
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
});

test('where json length', function () {
    $builder = getBuilder();
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
});

test('where between', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereBetween('votes', [1, 100]);

    $this->assertSame(
        'FOR userDoc IN users FILTER (userDoc.votes >= @'
        . $builder->aqb->getQueryId()
        . '_1 AND userDoc.votes <= @'
        . $builder->aqb->getQueryId()
        . '_2) RETURN userDoc',
        $builder->toSql()
    );
});

test('where not between', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereNotBetween('votes', [1, 100]);

    $this->assertSame(
        'FOR userDoc IN users FILTER (userDoc.votes < @'
        . $builder->aqb->getQueryId()
        . '_1 OR userDoc.votes > @'
        . $builder->aqb->getQueryId()
        . '_2) RETURN userDoc',
        $builder->toSql()
    );
});

test('where between columns', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereBetweenColumns('votes', ['min_vote', 'max_vote']);

    $this->assertSame(
        'FOR userDoc IN users FILTER (userDoc.votes >= userDoc.min_vote AND userDoc.votes <= userDoc.max_vote)'
        . ' RETURN userDoc',
        $builder->toSql()
    );
});

test('where column', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereColumn('first_name', '=', 'last_name');

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc.first_name == userDoc.last_name RETURN userDoc',
        $builder->toSql()
    );
});

test('where column without operator', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereColumn('first_name', 'last_name');

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc.first_name == userDoc.last_name RETURN userDoc',
        $builder->toSql()
    );
});

test('where nulls', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereNull('_key');
    $this->assertSame('FOR userDoc IN users FILTER userDoc._key == null RETURN userDoc', $builder->toSql());
    $this->assertEquals([], $builder->getBindings());

    $builder = getBuilder();
    $builder->select('*')
        ->from('users')
        ->where('id', '=', 1)
        ->orWhereNull('id');

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc._key == @'
        . $builder->aqb->getQueryId()
        . '_1 OR userDoc._key == null RETURN userDoc',
        $builder->toSql()
    );
    $this->assertEquals([$builder->aqb->getQueryId() . '_1' => 1], $builder->getBindings());
});

test('where not nulls', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereNotNull('id');
    $this->assertSame('FOR userDoc IN users FILTER userDoc._key != null RETURN userDoc', $builder->toSql());
    $this->assertEquals([], $builder->getBindings());

    $builder = getBuilder();
    $builder->select('*')
        ->from('users')
        ->where('id', '>', 1)
        ->orWhereNotNull('id');

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc._key > @'
        . $builder->aqb->getQueryId()
        . '_1 OR userDoc._key != null RETURN userDoc',
        $builder->toSql()
    );
    $this->assertEquals([$builder->aqb->getQueryId() . '_1' => 1], $builder->getBindings());
});

test('where in', function () {
    $builder = getBuilder();

    $builder->select()
        ->from('users')
        ->whereIn('country', ['The Netherlands', 'Germany', 'Great-Britain']);

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc.country IN @'
        . $builder->aqb->getQueryId()
        . '_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where integer in raw', function () {
    $builder = getBuilder();

    $builder->select()
        ->from('users')
        ->whereIntegerInRaw('country', [0, 1, 2, 3]);

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc.country IN @'
        . $builder->aqb->getQueryId()
        . '_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where not in', function () {
    $builder = getBuilder();

    $builder->select()
        ->from('users')
        ->whereNotIn('country', ['The Netherlands', 'Germany', 'Great-Britain']);

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc.country NOT IN @'
        . $builder->aqb->getQueryId()
        . '_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where integer not in raw', function () {
    $builder = getBuilder();

    $builder->select()
        ->from('users')
        ->whereIntegerNotInRaw('country', [0, 1, 2, 3]);

    $this->assertSame(
        'FOR userDoc IN users FILTER userDoc.country NOT IN @'
        . $builder->aqb->getQueryId()
        . '_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where date', function () {
    $builder = getBuilder();
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
});

test('where year', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereYear('created_at', '2016');

    $this->assertSame(
        'FOR userDoc IN users FILTER DATE_YEAR(userDoc.created_at) == @'
        . $builder->aqb->getQueryId()
        . '_1 RETURN userDoc',
        $builder->toSql()
    );

    $this->assertEquals([$builder->aqb->getQueryId() . '_1' => "2016"], $builder->getBindings());
});

test('where month', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereMonth('created_at', '12');

    $this->assertSame(
        'FOR userDoc IN users FILTER DATE_MONTH(userDoc.created_at) == @'
        . $builder->aqb->getQueryId()
        . '_1 RETURN userDoc',
        $builder->toSql()
    );

    $this->assertEquals([$builder->aqb->getQueryId() . '_1' => "12"], $builder->getBindings());
});

test('where day', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereDay('created_at', '31');

    $this->assertSame(
        'FOR userDoc IN users FILTER DATE_DAY(userDoc.created_at) == @'
        . $builder->aqb->getQueryId()
        . '_1 RETURN userDoc',
        $builder->toSql()
    );

    $this->assertEquals([$builder->aqb->getQueryId() . '_1' => "31"], $builder->getBindings());
});

test('where time', function () {
    $builder = getBuilder();
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
});

test('where nested', function () {
    $builder = getBuilder();

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
});

// Helpers
function getBuilder()
{
    $grammar = new Grammar();
    $processor = m::mock(Processor::class);

    return new Builder(m::mock(Connection::class), $grammar, $processor);
}
