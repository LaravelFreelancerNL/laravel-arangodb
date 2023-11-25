<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent;

use Illuminate\Database\Eloquent\Builder as IlluminateBuilder;
use Illuminate\Support\Arr;
use LaravelFreelancerNL\Aranguent\Eloquent\Concerns\QueriesAranguentRelationships;

class Builder extends IlluminateBuilder
{
    use QueriesAranguentRelationships;

    /**
     * The methods that should be returned from query builder.
     *
     * @var array
     */
    protected $passthru = [
        'insert', 'insertOrIgnore', 'insertGetId', 'insertUsing', 'getBindings', 'toSql', 'dump', 'dd',
        'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum', 'getConnection',
    ];

    /**
     * Get the first record matching the attributes or create it.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
     */
    public function firstOrCreate(array $attributes = [], array $values = [])
    {
        $instance = $this->where(associativeFlatten($attributes))->first();
        if (!is_null($instance)) {
            return $instance;
        }

        return tap($this->newModelInstance(array_merge($attributes, $values)), function ($instance) {
            $instance->save();
        });
    }

    /**
     * Get the first record matching the attributes or instantiate it.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
     */
    public function firstOrNew(array $attributes = [], array $values = [])
    {
        $instance = $this->where(associativeFlatten($attributes))->first();
        if (!is_null($instance)) {
            return $instance;
        }

        return $this->newModelInstance(array_merge($attributes, $values));
    }

    /**
     * Insert a record in the database.
     *
     *
     * @return int
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
        if (empty($values)) {
            return true;
        }

        if (Arr::isAssoc($values)) {
            $values = [$values];
        }
        if (!Arr::isAssoc($values)) {
            // Here, we will sort the insert keys for every record so that each insert is
            // in the same order for the record. We need to make sure this is the case
            // so there are not any errors or problems when inserting these records.
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        //Set timestamps
        foreach ($values as $key => $value) {
            $values[$key] = $this->updateTimestamps($value);
        }

        return $this->toBase()->insert($values);
    }

    /**
     * Add the "updated at" column to an array of values.
     *
     *
     * @return array
     */
    protected function updateTimestamps(array $values)
    {
        if (
            !$this->model->usesTimestamps() ||
            is_null($this->model->getUpdatedAtColumn()) ||
            is_null($this->model->getCreatedAtColumn())
        ) {
            return $values;
        }

        $timestamp = $this->model->freshTimestampString();
        $updatedAtColumn = $this->model->getUpdatedAtColumn();

        $timestamps = [];
        $timestamps[$updatedAtColumn] = $timestamp;

        $createdAtColumn = $this->model->getCreatedAtColumn();
        if (!isset($values[$createdAtColumn]) && !isset($this->model->$createdAtColumn)) {
            $timestamps[$createdAtColumn] = $timestamp;
        }

        $values = array_merge(
            $timestamps,
            $values
        );

        return $values;
    }

    /**
     * Add the "updated at" column to an array of values.
     */
    protected function addUpdatedAtColumn(array $values): array
    {
        if (
            !$this->model->usesTimestamps() ||
            is_null($this->model->getUpdatedAtColumn())
        ) {
            return $values;
        }

        $column = $this->model->getUpdatedAtColumn();

        $values = array_merge(
            [$column => $this->model->freshTimestampString()],
            $values
        );

        return $values;
    }
}
