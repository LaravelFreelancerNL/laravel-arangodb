<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Concerns;

use Illuminate\Support\Str;
use LaravelFreelancerNL\Aranguent\Eloquent\Builder;
use LaravelFreelancerNL\Aranguent\Query\Builder as QueryBuilder;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as ArangoQueryBuilder;

trait IsAranguentModel
{
    use HasAranguentRelationships;

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return '_key';
    }

    /**
     * @override
     * Create a new Eloquent query builder for the model.
     *
     * @param QueryBuilder $query
     *
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
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        // Laravel's accessors don't differentiate between id and _id, so we catch ArangoDB's _id here.
        if ($key === 'id') {
            return $this->attributes['_id'];
        }
        return $this->getAttribute($key);
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
            $this->attributes['_id'] = $value;
            return;
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

    /**
     * @param  string  $key
     */
    protected function updateIdWithKey(string $key)
    {
        if (! isset($this->attributes['_id'])) {
            return;
        }

        $id = preg_replace("/[a-zA-Z0-9_-]+\/\K.+/i", $key, $this->attributes['_id']);
        $this->attributes['_id'] = $id;
    }

    /**
     * Qualify the given column name by the model's table.
     *
     * @param string $column
     *
     * @return string
     */
    public function qualifyColumn($column)
    {
        $tableReferer = Str::singular($this->getTable()) . 'Doc';

        if (Str::startsWith($column, $tableReferer . '.')) {
            return $column;
        }

        return $tableReferer . '.' . $column;
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        $keyName = $this->getKeyName();
//
//        if ($keyName[0] != '_') {
//            $keyName = '_' . $keyName;
//        }

        return Str::snake(class_basename($this)) . $keyName;
    }

    protected function execute(ArangoQueryBuilder $aqb)
    {
        $connection = $this->getConnection();
        $results = $connection->execute($aqb->get());
        return $this->hydrate($results);
    }
}
