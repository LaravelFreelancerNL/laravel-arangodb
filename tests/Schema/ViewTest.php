<?php

declare(strict_types=1);

use ArangoClient\Exceptions\ArangoException;

test('createView', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    $view = $schemaManager->getView('search');

    expect($view->name)->toEqual('search');

    $schemaManager->deleteView('search');
});

test('getView', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    $view = Schema::getView('search');

    expect($view->name)->toEqual('search');

    $schemaManager->deleteView('search');
});

test('getAllViews', function () {
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

    $views = Schema::getAllViews();

    expect($views)->toHaveCount(5);
    expect($views[0]->name)->toBe('house_search_alias_view');
    expect($views[1]->name)->toBe('house_view');
    expect($views[2]->name)->toBe('pages');
    expect($views[3]->name)->toBe('products');
    expect($views[4]->name)->toBe('search');

    $schemaManager->deleteView('search');
    $schemaManager->deleteView('pages');
    $schemaManager->deleteView('products');
});

test('editView', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    Schema::editView('search', ['consolidationIntervalMsec' => 5]);

    $properties = $schemaManager->getViewProperties('search');

    expect($properties->consolidationIntervalMsec)->toEqual(5);

    $schemaManager->deleteView('search');
});

test('renameView', function () {
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

test('dropView', function () {
    $schemaManager = $this->connection->getArangoClient()->schema();
    if (!$schemaManager->hasView('search')) {
        Schema::createView('search', []);
    }
    Schema::dropView('search');

    $schemaManager->getView('search');
})->throws(ArangoException::class);

test('dropAllViews', function () {
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
