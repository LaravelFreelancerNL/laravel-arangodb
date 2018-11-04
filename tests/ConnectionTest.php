<?php

use LaravelFreelancerNL\Aranguent\Tests\TestCase;

class ConnectionTest extends TestCase
{

    /**
     * test connection
     * @test
     */
    function test_connection()
    {
        $this->assertInstanceOf('LaravelFreelancerNL\Aranguent\Connection', $this->connection);
    }
}