<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent;

use Illuminate\Database\Eloquent\Builder as IlluminateBuilder;

class Builder extends IlluminateBuilder
{

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
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        return $this->toBase()->update($this->updateTimestamps($values));
    }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
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

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        // Here, we will sort the insert keys for every record so that each insert is
        // in the same order for the record. We need to make sure this is the case
        // so there are not any errors or problems when inserting these records.
        else {
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
     * @param  array  $values
     * @return array
     */
    protected function updateTimestamps(array $values)
    {
        if (! $this->model->usesTimestamps() ||
            is_null($this->model->getUpdatedAtColumn()) ||
            is_null($this->model->getCreatedAtColumn())) {
            return $values;
        }
        $timestamp =  $this->model->freshTimestampString();
        $updatedAtColumn = $this->model->getUpdatedAtColumn();
        $createdAtColumn = $this->model->getCreatedAtColumn();
        $timestamps[$updatedAtColumn] = $timestamp;
        if (! isset($values[$createdAtColumn])) {
            $timestamps[$createdAtColumn] = $timestamp;
        }
        $values = array_merge(
            $timestamps,
            $values
        );

        return $values;
    }


}
