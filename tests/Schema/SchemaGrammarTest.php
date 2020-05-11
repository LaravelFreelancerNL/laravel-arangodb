<?php

namespace Tests\Schema;

use Illuminate\Support\Fluent;
use LaravelFreelancerNL\Aranguent\Schema\Grammar;
use Mockery as M;
use Tests\TestCase;

class SchemaGrammarTest extends TestCase
{
    /**
     * @var Grammar
     */
    protected $grammar;

    public function setUp(): void
    {
        parent::setUp();

        $this->grammar = new Grammar();
    }

    public function tearDown(): void
    {
        M::close();
    }

    /**
     * Compile has attribute returns the correct data.
     * @test
     */
    public function compile_has_attribute_returns_the_correct_data()
    {
        $collection = 'GrammarTestCollection';
        $attribute = [
            0 => 'firstAttribute',
            1 => 'SecondAttribute',
            2 => '@theThirdAttribute',
        ];

        $parameters['name'] = 'hasAttribute';
        $parameters['handler'] = 'aql';
        $parameters['explanation'] = "Checking if any document within the collection has the '".implode(', ', (array) $attribute)."' attribute(s).";
        $parameters['attribute'] = $attribute;
        $command = new Fluent($parameters);
        $results = $this->grammar->compileHasAttribute($collection, $command);

        $this->assertEquals('FOR doc IN GrammarTestCollection FILTER doc.firstAttribute != null AND doc.SecondAttribute != null AND doc.@theThirdAttribute != null LIMIT 1 RETURN true', $results->aqb->query);
    }

    /**
     * Compile drop attribute returns correct data.
     * @test
     */
    public function compile_drop_attribute_returns_correct_data()
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
        $this->assertEquals('FOR doc IN GrammarTestCollection FILTER doc.firstAttribute != null OR doc.SecondAttribute != null OR doc.@theThirdAttribute != null UPDATE doc WITH {"firstAttribute":null,"SecondAttribute":null,"@theThirdAttribute":null} IN GrammarTestCollection OPTIONS {"keepNull":false}',
            $results->aqb->query);
        $this->assertEquals([$collection], $results->aqb->collections['write']);
    }

    /**
     * Compile rename attribute returns correct data.
     * @test
     */
    public function compile_rename_attribute_returns_correct_data()
    {
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
        self::assertEquals('FOR doc IN GrammarTestCollection FILTER doc.oldAttributeName != null AND doc.newAttributeName == null UPDATE doc WITH {"oldAttributeName":null,"newAttributeName":doc.oldAttributeName} IN GrammarTestCollection OPTIONS {"keepNull":true}',
            $results->aqb->query);
        $this->assertEquals($collection, $results->aqb->collections['write'][0]);
    }

    /**
     * Attributes are correctly wrapped.
     * @test
     */
    public function attributes_are_correctly_wrapped()
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
