<?php

declare(strict_types=1);

use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('search', function () {
    $query = \DB::table('house_view')
        ->searchView('en.description', 'dragon lannister');

    $results = $query->get();

    expect($results->count())->toBe(2);
    expect($results[0]->name)->toBe('Targaryen');
    expect($results[1]->name)->toBe('Lannister');
});

test('search multiple fields', function () {
    $query = \DB::table('house_view')
        ->searchView(['en.description', 'en.words'], 'fire north');

    $results = $query->get();

    expect($results->count())->toBe(2);
    expect($results[0]->name)->toBe('Targaryen');
    expect($results[1]->name)->toBe('Stark');
});

test('search order by best matching', function () {
    $query = \DB::table('house_view')
        ->searchView(['en.description', 'en.words'], 'westeros dragon house fire north')
        ->orderByBestMatching();

    $results = $query->get();

    ray($query->toSql(), $results);

    expect($results->count())->toBe(3);
    expect($results[0]->name)->toBe('Stark');
    expect($results[1]->name)->toBe('Targaryen');
    expect($results[2]->name)->toBe('Lannister');
});

test('search order by frequency', function () {
    $query = \DB::table('house_view')
        ->searchView(['en.description', 'en.words'], 'westeros dragon house fire north')
        ->orderByFrequency();

    $results = $query->get();

    expect($results->count())->toBe(3);
    expect($results[0]->name)->toBe('Targaryen');
    expect($results[1]->name)->toBe('Stark');
    expect($results[2]->name)->toBe('Lannister');
});
