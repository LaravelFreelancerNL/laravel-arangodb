<?php

namespace LaravelFreelancerNL\Aranguent\Tests\Schema;

use Mockery as M;
use Illuminate\Support\Fluent;
use LaravelFreelancerNL\Aranguent\Schema\Grammars\Grammar;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;

class SchemaBuilderTest extends TestCase
{

    /**
     * @var \LaravelFreelancerNL\Aranguent\Schema\Grammars\Grammar $grammar
     */
    protected $grammar;

    public function setUp()
    {
        parent::setUp();

        $this->grammar = new Grammar();
    }

    public function tearDown()
    {
        M::close();
    }

    /**
     * Compile has attribute returns the correct data
     * @test
     */
    function compile_has_attribute_returns_the_correct_data()
    {
        $collection = 'GrammarTestCollection';
        $attribute = [
            0 => 'firstAttribute',
            1 => 'SecondAttribute',
            2 => '@theThirdAttribute',
        ];

        $parameters['name'] = 'hasAttribute';
        $parameters['handler'] = 'aql';
        $parameters['explanation'] = "Checking if any document within the collection has the '" . implode(', ', (array) $attribute) ."' attribute(s).";
        $parameters['attribute'] = $attribute;
        $command = new Fluent($parameters);
        $results = $this->grammar->compileHasAttribute($collection, $command);

        $this->assertEquals("FOR document IN @@collection
    FILTER @firstAttribute != null && @SecondAttribute != null && @`@theThirdAttribute` != null
    LIMIT 1
    RETURN true", $results->aql['query']);
    }

    /**
     * Compile drop attribute returns correct data
     * @test
     */
    function compile_drop_attribute_returns_correct_data()
    {
        $collection = 'GrammarTestCollection';
        $attributes = [
            0 => 'firstAttribute',
            1 => 'SecondAttribute',
            2 => '@theThirdAttribute',
        ];

        $parameters['name'] = 'dropAttribute';
        $parameters['handler'] = 'aql';
        $parameters['attributes'] = $attributes;
        $parameters['explanation'] = 'Drop the following attribute(s): '.implode(',', $attributes).'.';
        $command = new Fluent($parameters);
        $results = $this->grammar->compileDropAttribute($collection, $command);

        $this->assertEquals("FOR document IN @@collection
 FILTER @firstAttribute != null || @SecondAttribute != null || @`@theThirdAttribute` != null
 UPDATE document WITH {
     @firstAttribute: null,
     @SecondAttribute: null,
     @`@theThirdAttribute`: null,
} IN @@collection OPTIONS { keepNull: false }", $results->aql['query']);
        $this->assertEquals("`@theThirdAttribute`", $results->aql['bindings'][2]);
        $this->assertEquals($collection, $results->collections['write']);
        $this->assertEquals($collection, $results->collections['read']);

    }

    /**
     * Compile rename attribute returns correct data
     * @test
     */
    function compile_rename_attribute_returns_correct_data()
    {
        $collection = 'GrammarTestCollection';
        $from = 'oldAttributeName';
        $to = '@newAttributeName';
        
        $parameters['name'] = 'renameAttribute';
        $parameters['handler'] = 'aql';
        $parameters['explanation'] = "Rename the attribute '$from' to '$to'.";
        $parameters['from'] = $from;
        $parameters['to'] = $to;
        $command = new Fluent($parameters);
        $results = $this->grammar->compileRenameAttribute($collection, $command);

        $this->assertEquals("`@newAttributeName`", $results->aql['bindings']['from']);
        $this->assertEquals($collection, $results->collections['write']);
        $this->assertEquals($collection, $results->collections['read']);
    }

    /**
     * Attributes are correctly wrapped
     * @test
     */
    function attributes_are_correctly_wrapped()
    {
        $attributes = [
            'firstAttribute' => 'firstAttribute',
            '@secondAttribute' => '`@secondAttribute`',
        ];
        foreach (array_keys($attributes) as $attribute) {
            $wrappedAttribute = $this->grammar->wrapBindVar($attribute);
            $this->assertEquals($attributes[$attribute], $wrappedAttribute);
        }
    }

}