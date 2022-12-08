<?php

/**
 * @covers \LaravelFreelancerNL\Aranguent\Connection
 * @covers \LaravelFreelancerNL\Aranguent\Concerns\ManagesTransactions
 */

declare(strict_types=1);

use ArangoClient\Transactions\TransactionManager;
use Illuminate\Support\Facades\DB;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    M::close();
});

test('begin transaction', function () {
    $this->connection->beginTransaction();
    $arangoTransaction = $this->connection->getArangoClient()->getTransactionManager();
    $runningTransactions = $arangoTransaction->getTransactions();

    expect(count($runningTransactions))->toEqual(1);
    expect($this->connection->transactionLevel())->toEqual(1);
    expect($arangoTransaction)->toBeInstanceOf(TransactionManager::class);

    $this->connection->rollBack();
});

test('commit transaction', function () {
    $this->connection->beginTransaction();
    $this->connection->commit();
    $arangoTransaction = $this->connection->getArangoClient()->getTransactionManager();
    $runningTransactions = $arangoTransaction->getTransactions();

    expect($this->connection->transactionLevel())->toEqual(0);
    expect($runningTransactions)->toBeEmpty();
});

test('rollback transaction', function () {
    $startingCharacters = Character::all();
    $startingLocations = Location::all();

    $this->connection->beginTransaction(['write' => ['characters', 'locations']]);

    Character::create(
        [
            'id' => 'QuaitheOfTheShadow',
            'name' => 'Quaithe',
            'surname' => 'of the Shaadow',
            'alive' => true,
            'age' => null,
        ]
    );
    Location::create(
        [
            'id' => 'pyke',
            'name' => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );
    $this->connection->rollBack();

    $endingCharacters = Character::all();
    $endingLocations = Location::all();

    expect($startingCharacters->count())->toEqual(43);
    expect($endingCharacters->count())->toEqual(43);
    expect($startingLocations->count())->toEqual(9);
    expect($endingLocations->count())->toEqual(9);
});

test('commit transaction with queries', function () {
    $startingCharacters = Character::all();
    $startingLocations = Location::all();

    $this->connection->beginTransaction(['write' => ['characters', 'locations']]);

    Character::create(
        [
            'id' => 'QuaitheOfTheShadow',
            'name' => 'Quaithe',
            'surname' => 'of the Shaadow',
            'alive' => true,
            'age' => null,
        ]
    );
    Location::create(
        [
            'id' => 'pyke',
            'name' => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );

    $this->connection->commit();

    $endingCharacters = Character::all();
    $endingLocations = Location::all();

    expect($startingCharacters->count())->toEqual(43);
    expect($endingCharacters->count())->toEqual(44);
    expect($startingLocations->count())->toEqual(9);
    expect($endingLocations->count())->toEqual(10);

    // cleanup
    DB::delete('FOR doc IN characters FILTER doc._key == "QuaitheOfTheShadow" REMOVE doc IN characters');
    DB::delete('FOR doc IN locations FILTER doc._key == "pyke" REMOVE doc IN locations');
});

test('closure transactions', function () {
    DB::transaction(function () {
        DB::insert('FOR i IN 1..10 INSERT { _key: CONCAT("test", i) } INTO characters');

        DB::delete('FOR c IN characters FILTER c._key == "test10" REMOVE c IN characters');
    }, 5, ['write' => ['characters']]);

    $characters = Character::all();
    expect($characters->count())->toEqual(52);

    DB::delete('FOR doc IN characters FILTER doc._key LIKE "test%" REMOVE doc IN characters');
});
