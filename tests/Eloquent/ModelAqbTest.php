<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);

test('model by aql with query builder', function () {
    $results = Character::fromAqb(
        DB::aqb()
            ->for('characterDoc', 'characters')
            ->return('characterDoc')
    );

    expect($results)->toBeInstanceOf(Collection::class);
    expect($results->first())->toBeInstanceOf(Character::class);
});

test('model by aql with closure', function () {
    $results = Character::fromAqb(
        function ($aqb) {
            return $aqb->for('characterDoc', 'characters')
                ->return('characterDoc');
        }
    );

    expect($results)->toBeInstanceOf(Collection::class);
    expect($results->first())->toBeInstanceOf(Character::class);
});
