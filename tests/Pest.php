<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use LaravelFreelancerNL\Aranguent\Connection as Connection;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Mockery as m;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

/** @link https://pestphp.com/docs/underlying-test-case */

uses(
    TestCase::class,
)->in('./*');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/** @link https://pestphp.com/docs/expectations#custom-expectations */

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/** @link https://pestphp.com/docs/helpers */

function getBuilder()
{
    $grammar = new Grammar();
    $processor = m::mock(Processor::class);

    return new Builder(m::mock(Connection::class), $grammar, $processor);
}

function refreshDatabase()
{
    // TODO: add path to testbench migrations
    //migrate & seed
    Artisan::call('migrate:fresh', [
        '--path' => [
            database_path('migrations'),
            'tests/Setup/Database/Migrations',
            __DIR__ . '/../vendor/orchestra/testbench-core/laravel/migrations/'
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,
    ]);
}

function runCommand($command, $input = [])
{
    return $command->run(new ArrayInput($input), new NullOutput());
}
