<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent;

use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Support\Str;
use LaravelFreelancerNL\Aranguent\Query\Builder as QueryBuilder;

abstract class Model extends IlluminateModel
{
    use Concerns\HasRelationships;


    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = '_key';

    /**
     * The primary key type.
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @override
     * Create a new Eloquent query builder for the model.
     *
     * @param QueryBuilder $query
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
        return Str::snake(class_basename($this)).$this->getKeyName();
    }

}
