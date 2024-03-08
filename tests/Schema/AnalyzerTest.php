<?php

declare(strict_types=1);

use ArangoClient\Exceptions\ArangoException;

test('createAnalyzer', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasAnalyzer('myAnalyzer')) {
        Schema::createAnalyzer('myAnalyzer', [
            'type' => 'identity'
        ]);
    }
    $analyzer = $schemaManager->getAnalyzer('myAnalyzer');

    expect($analyzer->name)->toEqual('aranguent__test::myAnalyzer');

    $schemaManager->deleteAnalyzer('myAnalyzer');
});

test('getAllAnalyzers', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    $analyzers = Schema::getAllAnalyzers();

    expect($analyzers)->toHaveCount(13);
});

test('replaceAnalyzer', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasAnalyzer('myAnalyzer')) {
        Schema::createAnalyzer('myAnalyzer', [
            'type' => 'identity'
        ]);
    }

    Schema::replaceAnalyzer('myAnalyzer', [
        'type' => 'identity'
    ]);

    $schemaManager->deleteAnalyzer('myAnalyzer');
});

test('dropAnalyzer', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasAnalyzer('myAnalyzer')) {
        Schema::createAnalyzer('myAnalyzer', [
            'type' => 'identity'
        ]);
    }
    Schema::dropAnalyzer('myAnalyzer');

    $schemaManager->getAnalyzer('myAnalyzer');
})->throws(ArangoException::class);

test('dropAnalyzerIfExists true', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasAnalyzer('myAnalyzer')) {
        Schema::createAnalyzer('myAnalyzer', [
            'type' => 'identity'
        ]);
    }
    Schema::dropAnalyzerIfExists('myAnalyzer');

    $schemaManager->getAnalyzer('myAnalyzer');
})->throws(ArangoException::class);

test('dropAnalyzerIfExists false', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();

    Schema::dropAnalyzerIfExists('none-existing-analyzer');
});
