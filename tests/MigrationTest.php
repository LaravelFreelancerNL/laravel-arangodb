<?php

use LaravelFreelancerNL\Aranguent\Tests\TestCase;

class MigrationTest extends TestCase
{

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * make migration
     * @test
     */
    function make_migration()
    {
        $this->artisan('make:migration', ['name' => 'comments', '--create' => 'comments'])->run();
    }
}
