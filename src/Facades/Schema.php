<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Facades;

use Closure;
use Illuminate\Support\Facades\Schema as IlluminateSchema;
use LaravelFreelancerNL\Aranguent\Schema\Builder;

/**
 * Table handling:
 *
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
 */
class Schema extends IlluminateSchema
{
}
