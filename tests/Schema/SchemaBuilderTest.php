<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class SchemaBuilderTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }
}
