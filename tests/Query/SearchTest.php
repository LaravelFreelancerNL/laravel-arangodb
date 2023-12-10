<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('searchView', function () {
    $query = DB::table('house_view')
        ->searchView('en.description', 'dragon lannister');

    $results = $query->get();

    expect($results->count())->toBe(2);
    expect($results[0]->name)->toBe('Lannister');
    expect($results[1]->name)->toBe('Targaryen');
});

test('searchView with multiple fields', function () {
    $query = DB::table('house_view')
        ->searchView(['en.description', 'en.words'], 'fire north');

    $results = $query->get();

    expect($results->count())->toBe(2);
    expect($results[0]->name)->toBe('Stark');
    expect($results[1]->name)->toBe('Targaryen');
});

test('searchView and order by best matching', function () {
    $query = DB::table('house_view')
        ->searchView(['en.description', 'en.words'], 'westeros dragon house fire north')
        ->orderByBestMatching();

    $results = $query->get();

    expect($results->count())->toBe(3);
    expect($results[0]->name)->toBe('Stark');
    expect($results[1]->name)->toBe('Targaryen');
    expect($results[2]->name)->toBe('Lannister');
});

test('searchView and order by frequency', function () {
    $query = DB::table('house_view')
        ->searchView(['en.description', 'en.words'], 'westeros dragon house fire north')
        ->orderByFrequency();

    $results = $query->get();

    expect($results->count())->toBe(3);
    expect($results[0]->name)->toBe('Targaryen');
    expect($results[1]->name)->toBe('Stark');
    expect($results[2]->name)->toBe('Lannister');
});
