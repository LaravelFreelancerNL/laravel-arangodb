<?php

use Illuminate\Support\Facades\DB;
use Tests\Setup\Models\Character;

test('basic wheres', function () {
    $builder = getBuilder();
    $builder = $builder->select('*')
        ->from('users')
        ->where('id', '=', "a123");

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`_key` == @'
          . $builder->getQueryId()
        . '_where_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('basic wheres with multiple predicates', function () {
    $builder = getBuilder();
    $builder->select('*')
        ->from('users')
        ->where('id', '=', 1)
        ->where('email', '=', 'foo');

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`_key` == @'
        . $builder->getQueryId()
        . '_where_1 and `userDoc`.`email` == @'
        . $builder->getQueryId()
        . '_where_2 RETURN userDoc',
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
        'FOR userDoc IN users FILTER `userDoc`.`_key` == @'
        . $builder->getQueryId() . '_where_1 or `userDoc`.`email` == @'
        . $builder->getQueryId() . '_where_2 RETURN userDoc',
        $builder->toSql()
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
        . 'FILTER `userDoc`.`email` == @'
        . $builder->getQueryId()
        . '_where_1 and `userDoc`.`_key` != @'
        . $builder->getQueryId()
        . '_where_2 RETURN userDoc',
        $builder->toSql()
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
        . 'FILTER `userDoc`.`email`.`address` == @'
        . $builder->getQueryId()
        . '_where_1 and `userDoc`.`profile`.`address`.`street` != @'
        . $builder->getQueryId()
        . '_where_2 RETURN userDoc',
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
        . $builder->getQueryId()
        . '_where_1 IN `userDoc`.`options`.`languages` RETURN userDoc',
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
        . 'FILTER LENGTH(`userDoc`.`options`.`languages`) > @'
        . $builder->getQueryId()
        . '_where_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where between', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereBetween('votes', [1, 100]);

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`votes` >= @'
        . $builder->getQueryId()
        . '_where_1 AND `userDoc`.`votes` <= @'
        . $builder->getQueryId()
        . '_where_2 RETURN userDoc',
        $builder->toSql()
    );
});

test('where not between', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereNotBetween('votes', [1, 100]);

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`votes` < @'
        . $builder->getQueryId()
        . '_where_1 OR `userDoc`.`votes` > @'
        . $builder->getQueryId()
        . '_where_2 RETURN userDoc',
        $builder->toSql()
    );
});

test('where between columns', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereBetweenColumns('votes', ['min_vote', 'max_vote']);

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`votes` >= `userDoc`.`min_vote` AND `userDoc`.`votes` <= `userDoc`.`max_vote`'
        . ' RETURN userDoc',
        $builder->toSql()
    );
});

test('where column', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereColumn('first_name', '=', 'last_name');

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`first_name` == `userDoc`.`last_name` RETURN userDoc',
        $builder->toSql()
    );
});

test('where column without operator', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereColumn('first_name', 'last_name');

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`first_name` == `userDoc`.`last_name` RETURN userDoc',
        $builder->toSql()
    );
});

test('where nulls', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereNull('_key');
    expect($builder->toSql())->toBe('FOR userDoc IN users FILTER `userDoc`.`_key` == null RETURN userDoc');
    expect($builder->getBindings())->toEqual([]);

    $builder = getBuilder();
    $builder->select('*')
        ->from('users')
        ->where('id', '=', 1)
        ->orWhereNull('id');

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`_key` == @'
        . $builder->getQueryId()
        . '_where_1 or `userDoc`.`_key` == null RETURN userDoc',
        $builder->toSql()
    );
});

test('where not nulls', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereNotNull('id');
    expect($builder->toSql())->toBe('FOR userDoc IN users FILTER `userDoc`.`_key` != null RETURN userDoc');
    expect($builder->getBindings())->toEqual([]);

    $builder = getBuilder();
    $builder->select('*')
        ->from('users')
        ->where('id', '>', 1)
        ->orWhereNotNull('id');

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`_key` > @'
        . $builder->getQueryId()
        . '_where_1 or `userDoc`.`_key` != null RETURN userDoc',
        $builder->toSql()
    );
});

test('where in', function () {
    $builder = getBuilder();

    $builder->select()
        ->from('users')
        ->whereIn('country', ['The Netherlands', 'Germany', 'Great-Britain']);

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`country` IN @'
        . $builder->getQueryId()
        . '_where_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where integer in raw', function () {
    $builder = getBuilder();

    $builder->select()
        ->from('users')
        ->whereIntegerInRaw('country', [0, 1, 2, 3]);

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`country` IN [0, 1, 2, 3] RETURN userDoc',
        $builder->toSql()
    );
});

test('where not in', function () {
    $builder = getBuilder();

    $builder->select()
        ->from('users')
        ->whereNotIn('country', ['The Netherlands', 'Germany', 'Great-Britain']);

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`country` NOT IN @'
        . $builder->getQueryId()
        . '_where_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where integer not in raw', function () {
    $builder = getBuilder();

    $builder->select()
        ->from('users')
        ->whereIntegerNotInRaw('country', [0, 1, 2, 3]);

    $this->assertSame(
        'FOR userDoc IN users FILTER `userDoc`.`country` NOT IN [0, 1, 2, 3] RETURN userDoc',
        $builder->toSql()
    );
});

test('where date', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereDate('created_at', '2016-12-31');

    $this->assertSame(
        'FOR userDoc IN users FILTER DATE_FORMAT(`userDoc`.`created_at`, "%yyyy-%mm-%dd") == @'
        . $builder->getQueryId()
        . '_where_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where year', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereYear('created_at', '2016');

    $this->assertSame(
        'FOR userDoc IN users FILTER DATE_YEAR(`userDoc`.`created_at`) == @'
        . $builder->getQueryId()
        . '_where_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where month', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereMonth('created_at', '12');

    $this->assertSame(
        'FOR userDoc IN users FILTER DATE_MONTH(`userDoc`.`created_at`) == @'
        . $builder->getQueryId()
        . '_where_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where day', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereDay('created_at', '31');

    $this->assertSame(
        'FOR userDoc IN users FILTER DATE_DAY(`userDoc`.`created_at`) == @'
        . $builder->getQueryId()
        . '_where_1 RETURN userDoc',
        $builder->toSql()
    );
});

test('where time', function () {
    $builder = getBuilder();
    $builder->select('*')->from('users')->whereTime('created_at', '11:20:45');

    $this->assertSame(
        "FOR userDoc IN users FILTER DATE_FORMAT(`userDoc`.`created_at`, '%hh:%ii:%ss') == @"
        . $builder->getQueryId()
        . "_where_1 RETURN userDoc",
        $builder->toSql()
    );
});

test('subquery where', function () {
    $subquery = DB::table('locations')
        ->select('name')
        ->whereColumn('locations.led_by', 'characters.id')
        ->limit(1);

    $query = DB::table('characters')
        ->where($subquery, 'Winterfell');

    $characters = $query->get();

    expect($characters[0]->id)->toEqual('SansaStark');
});

test('where sub', function () {
    $query = Character::where('id', '==', function ($query) {
        $query->select('led_by')
            ->from('locations')
            ->where('name', 'Dragonstone')
            ->limit(1);
    });

    $characters = $query->get();

    expect($characters[0]->id)->toEqual('DaenerysTargaryen');
});

test('where exists with multiple results', function () {
    $query = Character::whereExists(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id');
    });

    $characters = $query->get();
    expect(count($characters))->toEqual(3);
});

test('where exists with limit', function () {
    $characters = Character::whereExists(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id')
            ->limit(1);
    })
        ->get();
    expect(count($characters))->toEqual(3);
});

test('where not exists with multiple results', function () {
    $query = Character::whereNotExists(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id');
    });

    $characters = $query->get();
    expect(count($characters))->toEqual(40);
});

test('where not exists with limit', function () {
    $query = Character::whereNotExists(function ($query) {
        $query->select('name')
            ->from('locations')
            ->whereColumn('locations.led_by', 'characters.id')
            ->limit(1);
    });

    $characters = $query->get();
    expect(count($characters))->toEqual(40);
});

test('where nested', function () {
    $builder = getBuilder();

    $query = $builder->select('*')
        ->from('characters')
        ->where('surname', '==', 'Lannister')
        ->where(function ($query) {
            $query->where('age', '>', 20)
                ->orWhere('alive', '=', true);
        });

    $binds = $query->getBindings();
    $bindKeys = array_keys($binds);

    $this->assertSame(
        'FOR characterDoc IN characters FILTER `characterDoc`.`surname` == @' . $bindKeys[0]
        . ' and ( `characterDoc`.`age` > @' . $bindKeys[1]
        . ' or `characterDoc`.`alive` == @' . $bindKeys[2]
        . ') RETURN characterDoc',
        $query->toSql()
    );
});

test('whereAll', function () {
    $query = \DB::table('houses')
        ->whereAll(['en.coat-of-arms', 'en.description'], 'LIKE', '%on%');

    $binds = $query->getBindings();
    $bindKeys = array_keys($binds);

    $this->assertSame(
        'FOR houseDoc IN houses FILTER ( `houseDoc`.`en`.`coat-of-arms` LIKE @' . $bindKeys[0]
        . ' and `houseDoc`.`en`.`description` LIKE @' . $bindKeys[1]
        . ') RETURN houseDoc',
        $query->toSql()
    );

    $results = $query->get();
    expect($results->count())->toBe(1);
    expect(($results->first())->name)->toBe('Targaryen');
});

test('whereAny', function () {
    $query = \DB::table('houses')
        ->whereAny(['en.coat-of-arms', 'en.description'], 'LIKE', '%on%');

    $binds = $query->getBindings();
    $bindKeys = array_keys($binds);

    $this->assertSame(
        'FOR houseDoc IN houses FILTER ( `houseDoc`.`en`.`coat-of-arms` LIKE @' . $bindKeys[0]
        . ' or `houseDoc`.`en`.`description` LIKE @' . $bindKeys[1]
        . ') RETURN houseDoc',
        $query->toSql()
    );

    $results = $query->get();
    expect($results->count())->toBe(3);
    expect(($results->first())->name)->toBe('Lannister');
});

test('orWhereAll', function () {
    $query = \DB::table('houses')
        ->whereAll(['en.coat-of-arms', 'en.description'], 'LIKE', '%on%')
        ->orWhereAll(['name', 'en.description'], 'LIKE', '%Stark%');

    $binds = $query->getBindings();
    $bindKeys = array_keys($binds);

    $this->assertSame(
        'FOR houseDoc IN houses FILTER ( `houseDoc`.`en`.`coat-of-arms` LIKE @' . $bindKeys[0]
        . ' and `houseDoc`.`en`.`description` LIKE @' . $bindKeys[1]
        . ') or ( `houseDoc`.`name` LIKE @' . $bindKeys[2]
        . ' and `houseDoc`.`en`.`description` LIKE @' . $bindKeys[3]
        . ') RETURN houseDoc',
        $query->toSql()
    );

    $results = $query->get();
    expect($results->count())->toBe(2);
    expect(($results->first())->name)->toBe('Stark');
});

test('orWhereAny', function () {
    $query = \DB::table('houses')
        ->whereAny(['en.coat-of-arms', 'en.description'], 'LIKE', '%Stark%')
        ->orWhereAny(['name', 'en.description'], 'LIKE', '%Dragon%');

    $binds = $query->getBindings();
    $bindKeys = array_keys($binds);

    $this->assertSame(
        'FOR houseDoc IN houses FILTER ( `houseDoc`.`en`.`coat-of-arms` LIKE @' . $bindKeys[0]
        . ' or `houseDoc`.`en`.`description` LIKE @' . $bindKeys[1]
        . ') or ( `houseDoc`.`name` LIKE @' . $bindKeys[2]
        . ' or `houseDoc`.`en`.`description` LIKE @' . $bindKeys[3]
        . ') RETURN houseDoc',
        $query->toSql()
    );

    $results = $query->get();
    expect($results->count())->toBe(2);
    expect(($results->first())->name)->toBe('Stark');
});
