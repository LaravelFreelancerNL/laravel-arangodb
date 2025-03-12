<?php

use ArangoClient\Exceptions\ArangoException;
use Illuminate\Support\Facades\Log;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Exceptions\QueryException;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseMigrations;
use Mockery as M;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

uses(DatabaseMigrations::class);

afterEach(function () {
    M::close();
});

test('has table', function () {
    expect(Schema::hasTable('locations'))->toBeTrue();
    expect(Schema::hasTable('dummy'))->toBeFalse();
});

test('has table throws on none existing database', function () {
    $oldDatabase = config('database.connections.arangodb.database');
    $newDatabase = 'otherDatabase';
    config()->set('database.connections.arangodb.database', $newDatabase);

    try {
        Schema::hasTable('dummy');
    } catch (QueryException $e) {
        expect($e)->toBeInstanceOf(QueryException::class);
    }
    config()->set('database.connections.arangodb.database', $oldDatabase);
});

test('rename', function () {
    $result = Schema::rename('characters', 'people');

    expect($result)->toBeTrue();
    expect(Schema::hasTable('characters'))->toBeFalse();
    expect(Schema::hasTable('people'))->toBeTrue();

    Schema::rename('people', 'characters');
});

test('drop all tables', function () {
    $initialTables = Schema::getTables();
    Schema::dropAllTables();

    $tables = Schema::getTables();

    expect(count($initialTables))->toEqual($this->tableCount);
    expect(count($tables))->toEqual(0);

    $this->artisan('migrate:install')->assertExitCode(0);
});

test('hasColumn', function () {
    expect(Schema::hasColumn('characters', 'xname'))->toBeFalse();
    expect(Schema::hasColumn('characters', 'name'))->toBeTrue();
});

test('hasColumns', function () {
    expect(Schema::hasColumn('characters', ['name', 'xname']))->toBeFalse();
    expect(Schema::hasColumn('characters', ['name', 'alive']))->toBeTrue();
});

test('create view', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    $view = $schemaManager->getView('search');

    expect($view->name)->toEqual('search');

    $schemaManager->deleteView('search');
});

test('get view', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    $view = Schema::getView('search');

    expect($view['name'])->toEqual('search');

    $schemaManager->deleteView('search');
});

test('get all views', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('pages')) {
        Schema::createView('pages', []);
    }
    if (!$schemaManager->hasView('products')) {
        Schema::createView('products', []);
    }
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }

    $views = Schema::getViews();

    expect($views)->toHaveCount(5);
    expect($views[0]['name'])->toBe('house_search_alias_view');
    expect($views[1]['name'])->toBe('house_view');
    expect($views[2]['name'])->toBe('pages');
    expect($views[3]['name'])->toBe('products');
    expect($views[4]['name'])->toBe('search');

    $schemaManager->deleteView('search');
    $schemaManager->deleteView('pages');
    $schemaManager->deleteView('products');
});

test('edit view', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    Schema::editView('search', ['consolidationIntervalMsec' => 5]);

    $properties = $schemaManager->getViewProperties('search');

    expect($properties->consolidationIntervalMsec)->toEqual(5);

    $schemaManager->deleteView('search');
});

test('rename view', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    Schema::renameView('search', 'find');

    $view = $schemaManager->getView('find');
    $schemaManager->deleteView('find');

    try {
        $schemaManager->getView('search');
    } catch (\ArangoClient\Exceptions\ArangoException $e) {
        $this->assertTrue(true);
    }

    expect($view->name)->toEqual('find');
});

test('drop view', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    Schema::dropView('search');

    $schemaManager->getView('search');
})->throws(ArangoException::class);

test('drop all views', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('products')) {
        Schema::createView('products', []);
    }
    if (!$schemaManager->hasView('pages')) {
        Schema::createView('pages', []);
    }
    Schema::dropAllViews();

    $this->assertFalse($schemaManager->hasView('products'));
    $this->assertFalse($schemaManager->hasView('search'));

    refreshDatabase();
});

test('create database', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    $databaseName = 'aranguent__test_dummy';
    $result = Schema::createDatabase($databaseName);

    expect($result)->toBeTrue();
    expect($schemaManager->hasDatabase($databaseName))->toBeTrue();

    $schemaManager->deleteDatabase($databaseName);
});

test('drop database if exists', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    $databaseName = 'aranguent__test_dummy';
    Schema::createDatabase($databaseName);

    $result = Schema::dropDatabaseIfExists($databaseName);

    expect($result)->toBeTrue();
    expect($schemaManager->hasDatabase($databaseName))->toBeFalse();
});

test('drop database if exists none existing db', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    $databaseName = 'aranguent__test_dummy';

    expect($schemaManager->hasDatabase($databaseName))->toBeFalse();

    $result = Schema::dropDatabaseIfExists($databaseName);

    expect($result)->toBeTrue();
});

test('get connection', function () {
    expect(Schema::getConnection())->toBeInstanceOf(Connection::class);
});

test('createAnalyzer', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasAnalyzer('myAnalyzer')) {
        Schema::createAnalyzer('myAnalyzer', 'identity');
    }
    $analyzer = $schemaManager->getAnalyzer('myAnalyzer');

    expect($analyzer->name)->toEqual('aranguent__test::myAnalyzer');

    $schemaManager->deleteAnalyzer('myAnalyzer');
});

test('getAnalyzers', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    $initialAnalyzers = $schemaManager->getAnalyzers();

    $analyzers = Schema::getAnalyzers();

    expect(count($initialAnalyzers))->toBe(count($analyzers));
});

test('replaceAnalyzer', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasAnalyzer('myAnalyzer')) {
        Schema::createAnalyzer('myAnalyzer', 'identity');
    }

    Schema::replaceAnalyzer('myAnalyzer', 'identity');

    $schemaManager->deleteAnalyzer('myAnalyzer');
});

test('dropAnalyzer', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasAnalyzer('myAnalyzer')) {
        Schema::createAnalyzer('myAnalyzer', 'identity');
    }
    Schema::dropAnalyzer('myAnalyzer');

    $schemaManager->getAnalyzer('myAnalyzer');
})->throws(ArangoException::class);

test('dropAnalyzerIfExists true', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasAnalyzer('myAnalyzer')) {
        Schema::createAnalyzer('myAnalyzer', 'identity');
    }
    Schema::dropAnalyzerIfExists('myAnalyzer');

    $schemaManager->getAnalyzer('myAnalyzer');
})->throws(ArangoException::class);

test('dropAnalyzerIfExists false', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    Schema::dropAnalyzerIfExists('none-existing-analyzer');
});

test('Silently fails unsupported functions', function () {
    Schema::nonExistingFunction('none-existing-analyzer');
})->throwsNoExceptions();

test('Unsupported functions are logged', function () {
    LogFake::bind();

    Schema::nonExistingFunction('none-existing-analyzer');

    Log::assertLogged(
        fn(LogEntry $log) => $log->level === 'warning',
    );
});
