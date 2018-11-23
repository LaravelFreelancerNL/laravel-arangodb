<?php

namespace LaravelFreelancerNL\Aranguent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \LaravelFreelancerNL\Aranguent\Schema\Builder create(string $collection, \Closure $callback)
 * @method static \LaravelFreelancerNL\Aranguent\Schema\Builder drop(string $collection)
 * @method static \LaravelFreelancerNL\Aranguent\Schema\Builder dropIfExists(string $collection)
 * @method static \LaravelFreelancerNL\Aranguent\Schema\Builder table(string $table, \Closure $callback)
 * @method static \LaravelFreelancerNL\Aranguent\Schema\Builder collection(string $collection, \Closure $callback)
 * @method static void defaultStringLength(int $length)
 *
 * @see \LaravelFreelancerNL\Aranguent\Schema\Builder
 */
class Schema extends Facade
{
    /**
     * Get a schema builder instance for a connection.
     *
     * @param  string  $name
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        return static::$app['db']->connection($name)->getSchemaBuilder();
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return static::$app['db']->connection()->getSchemaBuilder();
    }
}
