<?php

namespace LaravelFreelancerNL\Aranguent\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use LaravelFreelancerNL\Aranguent\Schema\Builder;

/**
 * Table handling:.
 * @method static Builder create($collection, Closure $callback, $options = [])
 * @method static Builder drop(string $collection)
 * @method static Builder dropIfExists(string $collection)
 * @method static Builder table(string $table, \Closure $callback)
 *
 * View handling:
 * @method static Builder createView($name, array $properties, $type = 'arangosearch')
 * @method static Builder getView(string $name)
 * @method static Builder editView($name, array $properties)
 * @method static Builder renameView(string $from, string $to)
 * @method static Builder dropView(string $name)
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
