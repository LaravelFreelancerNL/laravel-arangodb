<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Database\Seeds\HousesSeeder;
use Tests\Setup\Models\House;
use Tests\TestCase;

uses(TestCase::class);

test('search', function () {
    $builder = $this->getBuilder();
    $builder->select('*')
        ->from('page_view')
        ->search(true);

    $this->assertSame(
        'FOR pageViewDoc IN page_view SEARCH true RETURN pageViewDoc',
        $builder->toSql()
    );
});

test('search full predicate', function () {
    $builder = $this->getBuilder();
    $builder->select('*')
        ->from('user_view')
        ->search(['userViewDoc.age', '==', 20]);

    $this->assertSame(
        'FOR userViewDoc IN user_view SEARCH userViewDoc.age == 20 RETURN userViewDoc',
        $builder->toSql()
    );
});

test('search multiple predicates', function () {
    $builder = $this->getBuilder();
    $builder->select('*')
        ->from('user_view')
        ->search([['userViewDoc.age', '>=', 20], ['userViewDoc.age', '<=', 30]]);

    $this->assertSame(
        'FOR userViewDoc IN user_view SEARCH userViewDoc.age >= 20 AND userViewDoc.age <= 30 RETURN userViewDoc',
        $builder->toSql()
    );
});

test('search with options', function () {
    $builder = $this->getBuilder();
    $builder->select('*')
        ->from('user_view')
        ->search(
            [['userViewDoc.age', '>=', 20], ['userViewDoc.age', '<=', 30]],
            [
                'conditionOptimization' => 'none',
                'countApproximate' => 'cost'
            ]
        );

    $this->assertSame(
        'FOR userViewDoc IN user_view'
        . ' SEARCH userViewDoc.age >= 20 AND userViewDoc.age <= 30'
        . ' OPTIONS {"conditionOptimization":"none","countApproximate":"cost"}'
        . ' RETURN userViewDoc',
        $builder->toSql()
    );
});

test('search with aqb method', function () {
    $builder = $this->getBuilder();
    $builder->select('*')
        ->from('page_view')
        ->search($builder->aqb->analyzer('pageViewDoc.en.body_copy', '==', 'my search string', 'text_en'));

    $this->assertSame(
        'FOR pageViewDoc IN page_view'
        . ' SEARCH ANALYZER(pageViewDoc.en.body_copy == @' . $builder->aqb->getQueryId() . '_1,'
        . ' @' . $builder->aqb->getQueryId() . '_2)'
        . ' RETURN pageViewDoc',
        $builder->toSql()
    );
});

test('search against db', function () {
    $query = \DB::table('house_view');
    $query = $query->search(
        function ($aqb) {
            return $aqb->analyzer('houseViewDoc.en.description', '==', 'war', 'text_en');
        },
        ['waitForSync' => true]
    )->orderBy($query->aqb->bm25('houseViewDoc'), 'desc');

    $results = $query->paginate();

    $this->assertCount(2, $results);
    $this->assertSame("houses/lannister", $results[0]->_id);
    $this->assertSame("houses/stark", $results[1]->_id);
});

test('search from model', function () {
    $results = House::from('house_view')->search(
        function ($aqb) {
            return $aqb->analyzer('houseViewDoc.en.description', '==', 'war', 'text_en');
        },
        ['waitForSync' => true]
    )
        ->paginate();

    $this->assertCount(2, $results);
    $this->assertSame("houses/lannister", $results[0]->_id);
    $this->assertInstanceOf(House::class, $results[0]);
    $this->assertSame("houses/stark", $results[1]->_id);
    $this->assertInstanceOf(House::class, $results[1]);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

    Artisan::call('db:seed', ['--class' => HousesSeeder::class]);
}
