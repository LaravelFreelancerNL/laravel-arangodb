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

    $this->assertEquals(1, count($runningTransactions));
    $this->assertEquals(1, $this->connection->transactionLevel());
    $this->assertInstanceOf(TransactionManager::class, $arangoTransaction);

    $this->connection->rollBack();
});

test('commit transaction', function () {
    $this->connection->beginTransaction();
    $this->connection->commit();
    $arangoTransaction = $this->connection->getArangoClient()->getTransactionManager();
    $runningTransactions = $arangoTransaction->getTransactions();

    $this->assertEquals(0, $this->connection->transactionLevel());
    $this->assertEmpty($runningTransactions);
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

    $this->assertEquals(0, $startingCharacters->count());
    $this->assertEquals(0, $endingCharacters->count());
    $this->assertEquals(0, $startingLocations->count());
    $this->assertEquals(0, $endingLocations->count());
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

    $this->assertEquals(0, $startingCharacters->count());
    $this->assertEquals(1, $endingCharacters->count());
    $this->assertEquals(0, $startingLocations->count());
    $this->assertEquals(1, $endingLocations->count());
});

test('closure transactions', function () {
    DB::transaction(function () {
        DB::insert('FOR i IN 1..10 INSERT { _key: CONCAT("test", i) } INTO characters');

        DB::delete('FOR c IN characters FILTER c._key == "test10" REMOVE c IN characters');
    }, 5, ['write' => ['characters']]);

    $characters = Character::all();
    $this->assertEquals(9, $characters->count());
});

// Helpers
function defineDatabaseMigrations()
{
    test()->loadLaravelMigrations();
    test()->loadMigrationsFrom(__DIR__ . '/Setup/Database/Migrations');
}
