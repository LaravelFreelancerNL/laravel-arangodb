<?php

namespace Tests\Mocks;

use ArangoDBClient\Connection;

class MockArangoConnection extends Connection
{
    public function __construct(array $options = [])
    {
        parent::__construct();
    }
}
