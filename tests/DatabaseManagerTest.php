<?php

namespace Tests;

use LaravelFreelancerNL\Aranguent\DatabaseManager;

class DatabaseManagerTest extends TestCase
{
    public function testDatabaseManagerIsRegistered()
    {
        $db = app('db');

        $this->assertInstanceOf(DatabaseManager::class, $db);
    }
}
