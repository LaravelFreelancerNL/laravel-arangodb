<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Eloquent\Builder;
use LaravelFreelancerNL\Aranguent\Query\Builder as QueryBuilder;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as ArangoQueryBuilder;

trait IsAranguentModel
{
    use HasAranguentRelationships;

    /**
     * Insert the given attributes and set the ID on the model.
     *
     * @param  array  $attributes
     * @return void
     */
    protected function insertAndSetId(\Illuminate\Database\Eloquent\Builder $query, $attributes)
    {
        $keyName = $this->getKeyName();
        $id = $query->insertGetId($attributes, $keyName);

        $this->setAttribute($keyName, $id);
        if ($keyName === '_id') {
            $matches = [];
            preg_match('/\/(.*)$/', $id, $matches);

            $this->setAttribute('id', $matches[1]);
        }
        if ($keyName === 'id' || $keyName === '_key') {
            $this->updateIdWithKey($id);
        }
    }

    /**
     * @override
     * Create a new Eloquent query builder for the model.
     *
     * @param  QueryBuilder  $query
     * @return Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        return $this->getConnection()->query();
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        // Laravel's mutators don't differentiate between id and _id, so we catch ArangoDB's _id here.
        if ($key === 'id') {
            $this->updateIdWithKey($value);
        }

        if ($key === '_id') {
            $this->attributes['id'] = explode('/', $value)[1];
        }

        $this->setAttribute($key, $value);
    }

    /**
     * Map the id attribute commonly used in Laravel to the primary key for third-party compatibility.
     * In ArangoDB '_key' is the equivalent of 'id' in sql databases.
     *
     * @param  string  $value
     * @return void
     */
    public function setKeyAttribute($value)
    {
        $this->attributes['_key'] = $value;

        $this->updateIdWithKey($value);
    }

    protected function updateIdWithKey(string $key)
    {
        $this->attributes['_id'] = $this->getTable().'/'.$key;
    }

    /**
     * Qualify the given column name by the model's table.
     *
     * @param  string  $column
     * @return string
     */
    public function qualifyColumn($column)
    {
        $tableReferer = Str::singular($this->getTable()).'Doc';

        if (Str::startsWith($column, $tableReferer.'.')) {
            return $column;
        }

        return $tableReferer.'.'.$column;
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        $keyName = $this->getKeyName();

        if ($keyName[0] != '_') {
            $keyName = '_'.$keyName;
        }

        return Str::snake(class_basename($this)).$keyName;
    }

    public function fromAqb(ArangoQueryBuilder|Closure $aqb): Collection
    {
        if ($aqb instanceof Closure) {
            /** @phpstan-ignore-next-line */
            $aqb = $aqb(DB::aqb());
        }
        $connection = $this->getConnection();
        $results = $connection->execute($aqb->get());

        return $this->hydrate($results);
    }

    /**
     * Get the database connection for the model.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return static::resolveConnection($this->getConnectionName());
    }
}
