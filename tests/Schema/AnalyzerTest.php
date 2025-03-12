<?php

declare(strict_types=1);

use ArangoClient\Exceptions\ArangoException;

test('createAnalyzer', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasAnalyzer('myAnalyzer')) {
        Schema::createAnalyzer(
            'myAnalyzer',
            'identity',
        );
    }
    $analyzer = $schemaManager->getAnalyzer('myAnalyzer');

    expect($analyzer->name)->toEqual('aranguent__test::myAnalyzer');

    $schemaManager->deleteAnalyzer('myAnalyzer');
});

test('getAnalyzers', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    $initialAnalyzers = $schemaManager->getAnalyzers();

    $analyzers = Schema::getAnalyzers();

    $endAnalyzers = $schemaManager->getAnalyzers();
    expect(count($initialAnalyzers))->toBe(count($endAnalyzers));
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

test('dropAllAnalyzers', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    $initialAnalyzers = Schema::getAnalyzers();

    Schema::createAnalyzer('myAnalyzer1', 'identity');
    Schema::createAnalyzer('myAnalyzer2', 'identity');

    $totalAnalyzers = Schema::getAnalyzers();

    Schema::dropAllAnalyzers();

    $endAnalyzers = Schema::getAnalyzers();

    expect(count($initialAnalyzers))->toBe(count($endAnalyzers));
    expect(count($initialAnalyzers))->toBe(count($totalAnalyzers) - 2);
});
