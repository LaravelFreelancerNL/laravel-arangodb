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
