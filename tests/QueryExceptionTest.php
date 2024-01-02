<?php

use Illuminate\Database\QueryException as IlluminateQueryException;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\Exceptions\QueryException;

test('bad query throws query exception', function () {
    $this->expectException(QueryException::class);

    DB::execute("this is not AQL", ['testBind' => 'test']);
});

test('query exception extends illuminate', function () {
    $this->expectException(IlluminateQueryException::class);

    DB::execute("this is not AQL", ['testBind' => 'test']);
});

test('query exception has correct message', function () {
    $this->expectExceptionMessage(
        "400 - AQL: syntax error, unexpected identifier near 'this is not AQL' at position 1:1 (while parsing)"
        . " (Connection: arangodb,AQL: this is not AQL - Bindings: array (\n"
        . "  'testBind' => 'test',\n))"
    );

    DB::execute('this is not AQL', ['testBind' => 'test']);
});

test('query exception without binds', function () {
    expect(fn() => DB::execute("this is not AQL", []))->toThrow(QueryException::class);
});
