<?php

namespace Tests;

use Illuminate\Database\QueryException as IlluminateQueryException;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\QueryException;

class QueryExceptionTest extends TestCase
{
    public function testBadQueryThrowsQueryException()
    {
        $this->expectException(QueryException::class);

        DB::execute('this ain\'t AQL', ['testBind' => 'test']);
    }

    public function testQueryExceptionExtendsIlluminate()
    {
        $this->expectException(IlluminateQueryException::class);

        DB::execute('this ain\'t AQL', ['testBind' => 'test']);
    }

    public function testQueryExceptionHasCorrectMessage()
    {
        $this->expectExceptionMessage(
            "400 - AQL: syntax error, unexpected identifier near 'this ain't AQL' at position 1:1 (while parsing)"
            . " (AQL: this ain't AQL - Bindings: array (\n"
            . "  'testBind' => 'test',\n))"
        );

        DB::execute('this ain\'t AQL', ['testBind' => 'test']);
    }

    public function testQueryExceptionWithoutBinds()
    {
        $this->expectExceptionMessage(
            "400 - AQL: syntax error, unexpected identifier near 'this ain't AQL' at position 1:1 (while parsing)"
            . " (AQL: this ain't AQL - Bindings: array (\n"
            . "))"
        );

        DB::execute('this ain\'t AQL', []);
    }
}
