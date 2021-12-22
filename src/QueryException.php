<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent;

use Illuminate\Database\QueryException as IlluminateQueryException;
use Throwable;

class QueryException extends IlluminateQueryException
{
    /**
     * Format the SQL error message.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Throwable  $previous
     * @return string
     */
    protected function formatMessage($sql, $bindings, Throwable $previous)
    {
        return $previous->getMessage()
            . ' (AQL: ' . $sql
            . ' - Bindings: ' . var_export($bindings, true)
            . ')';
    }
}
