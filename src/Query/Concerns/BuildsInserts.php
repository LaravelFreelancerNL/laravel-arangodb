<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use LaravelFreelancerNL\FluentAQL\Exceptions\BindException;

trait BuildsInserts
{
    /**
     * Insert a new record into the database.
     *
     * @param array<mixed> $values
     * @throws BindException
     */
    public function insert(array $values): bool
    {
        if (!array_is_list($values)) {
            $values = [$values];
        }

        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        $bindVar = $this->bindValue($values, 'insert');

        $aql = $this->grammar->compileInsert($this, $values, $bindVar);
        return $this->getConnection()->insert($aql, $this->getBindings());
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param array<mixed> $values
     */
    public function insertGetId(array $values, $sequence = null)
    {
        if (!array_is_list($values)) {
            $values = [$values];
        }

        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        $bindVar = $this->bindValue($values, 'insert');

        $aql = $this->grammar->compileInsertGetId($this, $values, $sequence, $bindVar);
        $response = $this->getConnection()->execute($aql, $this->getBindings());

        return (is_array($response)) ? end($response) : $response;
    }

    /**
     * Insert a new record into the database.
     *
     * @param array<mixed> $values
     *
     * @throws BindException
     */
    public function insertOrIgnore(array $values): bool
    {
        if (!array_is_list($values)) {
            $values = [$values];
        }

        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        $bindVar = $this->bindValue($values, 'insert');


        $aql = $this->grammar->compileInsertOrIgnore($this, $values, $bindVar);
        $results = $this->getConnection()->insert($aql, $this->getBindings());

        return $results;
    }
}
