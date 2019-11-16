<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use LaravelFreelancerNL\Aranguent\Query\Builder as QueryBuilder;


abstract class Model extends IlluminateModel
{
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
}
