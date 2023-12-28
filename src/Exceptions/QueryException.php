<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Exceptions;

use Illuminate\Database\QueryException as IlluminateQueryException;
use Throwable;

class QueryException extends IlluminateQueryException
{
    /**
     * Format the SQL error message.
     *
     * @param  string  $sql
     * @param  array<mixed>  $bindings
     * @return string
     */
    protected function formatMessage($connectionName, $sql, $bindings, Throwable $previous)
    {
        return $previous->getMessage()
            . ' (Connection: ' . $connectionName
            . ',AQL: ' . $sql
            . ' - Bindings: ' . var_export($bindings, true)
            . ')';
    }
}
