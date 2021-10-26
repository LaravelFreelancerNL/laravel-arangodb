<?php

namespace LaravelFreelancerNL\Aranguent\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use LaravelFreelancerNL\Aranguent\Schema\Builder;

/**
 * Table handling:
 * @method static Builder create($collection, Closure $callback, $options = [])
 * @method static Builder getAllTables()
 * @method static Builder drop(string $collection)
 * @method static Builder dropIfExists(string $collection)
 * @method static Builder dropAllTables()
 * @method static Builder table(string $table, \Closure $callback)
 * @method static Builder hasColumn(string $table, $column)
 *
 * View handling:
 * @method static Builder createView($name, array $properties, $type = 'arangosearch')
 * @method static Builder getView(string $name)
 * @method static Builder editView($name, array $properties)
 * @method static Builder renameView(string $from, string $to)
 * @method static Builder dropView(string $name)
 * @method static Builder dropAllViews()
 *
 * @see \LaravelFreelancerNL\Aranguent\Schema\Builder
 *
 * @deprecated This facade isn't necessary anymore and will be removed at version 1.0
 */
class Schema extends Facade
{
    /**
     * Get a schema builder instance for a connection.
     *
     * @SuppressWarnings(PHPMD.UndefinedVariable)
     *
     * @param string $name
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        return static::$app['db']->connection($name)->getSchemaBuilder();
    }

    /**
     * Get the registered name of the component.
     *
     * @SuppressWarnings(PHPMD.UndefinedVariable)
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return static::$app['db']->connection()->getSchemaBuilder();
    }
}
