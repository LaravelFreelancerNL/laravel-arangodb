<?php

use ArangoClient\Transactions\TransactionManager;
use Illuminate\Support\Facades\DB;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;

uses(TestCase::class);

/**
 * @covers \LaravelFreelancerNL\Aranguent\Connection
 * @covers \LaravelFreelancerNL\Aranguent\Concerns\ManagesTransactions
 */
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
            '_key'    => 'TheonGreyjoy',
            'name'    => 'Theon',
            'surname' => 'Greyjoy',
            'alive'   => true,
            'age'     => 16,
            'traits'  => ['E', 'R', 'K'],
        ]
    );
    Location::create(
        [
            '_key'       => 'pyke',
            'name'       => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );
    $this->connection->rollBack();
    $this->connection->commit();

    $endingCharacters = Character::all();
    $endingLocations = Location::all();

    expect($startingCharacters->count())->toEqual(0);
    expect($endingCharacters->count())->toEqual(0);
    expect($startingLocations->count())->toEqual(0);
    expect($endingLocations->count())->toEqual(0);
});

test('commit transaction with queries', function () {
    $startingCharacters = Character::all();
    $startingLocations = Location::all();

    $this->connection->beginTransaction(['write' => ['characters', 'locations']]);

    Character::create(
        [
            '_key'    => 'TheonGreyjoy',
            'name'    => 'Theon',
            'surname' => 'Greyjoy',
            'alive'   => true,
            'age'     => 16,
            'traits'  => ['E', 'R', 'K'],
        ]
    );
    Location::create(
        [
            '_key'       => 'pyke',
            'name'       => 'Pyke',
            'coordinate' => [55.8833342, -6.1388807],
        ]
    );

    $this->connection->commit();

    $endingCharacters = Character::all();
    $endingLocations = Location::all();

    expect($startingCharacters->count())->toEqual(0);
    expect($endingCharacters->count())->toEqual(1);
    expect($startingLocations->count())->toEqual(0);
    expect($endingLocations->count())->toEqual(1);
});

test('closure transactions', function () {
    DB::transaction(function () {
        DB::insert('FOR i IN 1..10 INSERT { _key: CONCAT("test", i) } INTO characters');

        DB::delete('FOR c IN characters FILTER c._key == "test10" REMOVE c IN characters');
    }, 5, ['write' => ['characters']]);

    $characters = Character::all();
    expect($characters->count())->toEqual(9);
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/Setup/Database/Migrations');
}
