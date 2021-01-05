<?php

namespace Tests;

use ArangoDBClient\StreamingTransaction;
use ArangoDBClient\StreamingTransactionHandler;
use Illuminate\Support\Facades\DB;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;

class TransactionsTest extends TestCase
{
    public function tearDown(): void
    {
        M::close();
    }

    public function testGetTransactionHandler()
    {
        $transactionHandler = $this->connection->getTransactionHandler();
        $this->assertInstanceOf(StreamingTransactionHandler::class, $transactionHandler);
    }

    public function testBeginTransaction()
    {
        $connection = $this->connection;
        $connection->beginTransaction();
        $arangoTransaction = $connection->getArangoTransaction();

        $this->assertEquals(1, $connection->transactionLevel());
        $this->assertInstanceOf(StreamingTransaction::class, $arangoTransaction);
    }

    public function testCommitTransaction()
    {
        $connection = $this->connection;
        $connection->beginTransaction();
        $connection->commit();
        $arangoTransaction = $connection->getArangoTransaction();

        $this->assertEquals(0, $connection->transactionLevel());
        $this->assertNull($arangoTransaction);
    }

    public function testCommitTransactionWithQueries()
    {
        $startingCharacters = Character::all();
        $startingLocations = Location::all();

        $connection = $this->connection;
        $connection->beginTransaction(['write' => ['characters', 'locations']]);

        $character = Character::create(
            [
                '_key'    => 'TheonGreyjoy',
                'name'    => 'Theon',
                'surname' => 'Greyjoy',
                'alive'   => true,
                'age'     => 16,
                'traits'  => ['E', 'R', 'K'],
            ]
        );
        $location = Location::create(
            [
                '_key'       => 'pyke',
                'name'       => 'Pyke',
                'coordinate' => [55.8833342, -6.1388807],
            ]
        );

        $connection->commit();

        $endingCharacters = Character::all();
        $endingLocations = Location::all();

        $this->assertEquals(0, $startingCharacters->count());
        $this->assertEquals(1, $endingCharacters->count());
        $this->assertEquals(0, $startingLocations->count());
        $this->assertEquals(1, $endingLocations->count());
    }

    public function testRollbackTransaction()
    {
        $startingCharacters = Character::all();
        $startingLocations = Location::all();

        $connection = $this->connection;
        $connection->beginTransaction(['write' => ['characters', 'locations']]);

        $character = Character::create(
            [
                '_key'    => 'TheonGreyjoy',
                'name'    => 'Theon',
                'surname' => 'Greyjoy',
                'alive'   => true,
                'age'     => 16,
                'traits'  => ['E', 'R', 'K'],
            ]
        );
        $location = Location::create(
            [
                '_key'       => 'pyke',
                'name'       => 'Pyke',
                'coordinate' => [55.8833342, -6.1388807],
            ]
        );
        $connection->rollBack();
        $connection->commit();

        $endingCharacters = Character::all();
        $endingLocations = Location::all();

        $this->assertEquals(0, $startingCharacters->count());
        $this->assertEquals(0, $endingCharacters->count());
        $this->assertEquals(0, $startingLocations->count());
        $this->assertEquals(0, $endingLocations->count());
    }

    public function testClosureTransactions()
    {
        DB::transaction(function () {
            DB::insert('FOR i IN 1..10 INSERT { _key: CONCAT("test", i) } INTO characters');

            DB::delete('FOR c IN characters FILTER c._key == "test10" REMOVE c IN characters');
        }, 5, ['write' => ['characters']]);

        $characters = Character::all();
        $this->assertEquals(9, $characters->count());
    }
}
