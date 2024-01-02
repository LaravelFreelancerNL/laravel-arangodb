<?php

use Illuminate\Support\Fluent;
use LaravelFreelancerNL\Aranguent\Schema\Grammar;
use Mockery as M;

beforeEach(function () {
    $this->grammar = new Grammar();
});

afterEach(function () {
    M::close();
});

test('compile has attribute returns correct data', function () {
    $collection = 'GrammarTestCollection';
    $attributes = [
        0 => 'firstAttribute',
        1 => 'SecondAttribute',
        2 => '@theThirdAttribute',
    ];

    $parameters['name'] = 'hasAttribute';
    $parameters['handler'] = 'aql';
    $parameters['explanation'] = "Checking if any document within the collection has the '"
        . implode(', ', $attributes) . "' attribute(s).";
    $parameters['columns'] = $attributes;
    $command = new Fluent($parameters);
    $results = $this->grammar->compileHasColumn($collection, $command);

    $this->assertEquals(
        'FOR doc IN GrammarTestCollection FILTER doc.firstAttribute != null AND doc.SecondAttribute != null '
        . 'AND doc.@theThirdAttribute != null LIMIT 1 RETURN true',
        $results->aqb
    );
});

test('compile drop attribute returns correct data', function () {
    $collection = 'GrammarTestCollection';
    $attributes = [
        0 => 'firstAttribute',
        1 => 'SecondAttribute',
        2 => '@theThirdAttribute',
    ];

    $parameters['name'] = 'dropAttribute';
    $parameters['handler'] = 'aql';
    $parameters['attributes'] = $attributes;
    $parameters['explanation'] = 'Drop the following attribute(s): ' . implode(',', $attributes) . '.';
    $command = new Fluent($parameters);
    $results = $this->grammar->compileDropAttribute($collection, $command);
    $this->assertEquals(
        'FOR doc IN GrammarTestCollection FILTER doc.firstAttribute != null OR doc.SecondAttribute != null '
        . 'OR doc.@theThirdAttribute != null UPDATE doc WITH '
        . '{"firstAttribute":null,"SecondAttribute":null,"@theThirdAttribute":null} '
        . 'IN GrammarTestCollection OPTIONS {"keepNull":false}',
        $results->aqb
    );
    expect($results->aqb->collections['write'])->toEqual([$collection]);
});

test('compile rename attribute returns correct data', function () {
    $collection = 'GrammarTestCollection';
    $from = 'oldAttributeName';
    $to = 'newAttributeName';

    $parameters['name'] = 'renameAttribute';
    $parameters['handler'] = 'aql';
    $parameters['explanation'] = "Rename the attribute '$from' to '$to'.";
    $parameters['from'] = $from;
    $parameters['to'] = $to;
    $command = new Fluent($parameters);
    $results = $this->grammar->compileRenameAttribute($collection, $command);
    self::assertEquals(
        'FOR doc IN GrammarTestCollection FILTER doc.oldAttributeName != null AND doc.newAttributeName == null'
        . ' UPDATE doc WITH {"oldAttributeName":null,"newAttributeName":doc.oldAttributeName} '
        . 'IN GrammarTestCollection OPTIONS {"keepNull":true}',
        $results->aqb->query
    );
    expect($results->aqb->collections['write'][0])->toEqual($collection);
});

test('attributes are wrapped correctly', function () {
    $attributes = [
        'firstAttribute' => 'firstAttribute',
        '@secondAttribute' => '`@secondAttribute`',
    ];
    foreach (array_keys($attributes) as $attribute) {
        $wrappedAttribute = $this->grammar->wrapBindVar($attribute);
        expect($wrappedAttribute)->toEqual($attributes[$attribute]);
    }
});
