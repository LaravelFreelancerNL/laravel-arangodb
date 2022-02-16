<?php

declare(strict_types=1);

use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\House;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('search', function () {
    $builder = getBuilder();
    $builder->select('*')
        ->from('page_view')
        ->search(true);

    $this->assertSame(
        'FOR pageViewDoc IN page_view SEARCH true RETURN pageViewDoc',
        $builder->toSql()
    );
});

test('search full predicate', function () {
    $builder = getBuilder();
    $builder->select('*')
        ->from('user_view')
        ->search(['userViewDoc.age', '==', 20]);

    $this->assertSame(
        'FOR userViewDoc IN user_view SEARCH userViewDoc.age == 20 RETURN userViewDoc',
        $builder->toSql()
    );
});

test('search multiple predicates', function () {
    $builder = getBuilder();
    $builder->select('*')
        ->from('user_view')
        ->search([['userViewDoc.age', '>=', 20], ['userViewDoc.age', '<=', 30]]);

    $this->assertSame(
        'FOR userViewDoc IN user_view SEARCH userViewDoc.age >= 20 AND userViewDoc.age <= 30 RETURN userViewDoc',
        $builder->toSql()
    );
});

test('search with options', function () {
    $builder = getBuilder();
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
    $builder = getBuilder();
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
        }
    )->orderBy($query->aqb->bm25('houseViewDoc'), 'desc');

    $results = $query->paginate();

    expect($results)->toHaveCount(2);
    expect($results[0]->_id)->toBe("houses/lannister");
    expect($results[1]->_id)->toBe("houses/stark");
});

test('search from model', function () {
    $results = House::from('house_view')->search(
        function ($aqb) {
            return $aqb->analyzer('houseViewDoc.en.description', '==', 'war', 'text_en');
        }
    )
        ->paginate();

    expect($results)->toHaveCount(2);
    expect($results[0]->_id)->toBe("houses/lannister");
    expect($results[0])->toBeInstanceOf(House::class);
    expect($results[1]->_id)->toBe("houses/stark");
    expect($results[1])->toBeInstanceOf(House::class);
});
