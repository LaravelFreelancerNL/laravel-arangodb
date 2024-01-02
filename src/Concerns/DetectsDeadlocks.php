<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Concerns;

use Exception;

trait DetectsDeadlocks
{
    /**
     * Determine if the given exception was caused by a deadlock.
     * @see https://www.arangodb.com/docs/stable/appendix-error-codes.html
     *
     * @return bool
     */
    protected function causedByDeadlock(Exception $e)
    {
        $code = $e->getCode();

        return in_array($code, [
            18,     // ERROR_LOCK_TIMEOUT
            28,     // ERROR_LOCKED
            29,     // ERROR_DEADLOCK
            1200,   // ERROR_ARANGO_CONFLICT (write-write conflict)
            1302,   // ERROR_ARANGO_TRY_AGAIN
            1303,   // ERROR_ARANGO_BUSY
            1304,   // ERROR_ARANGO_MERGE_IN_PROGRESS
            1521,   // ERROR_QUERY_COLLECTION_LOCK_FAILED
            7009,   // ERROR_LOCAL_LOCK_FAILED
            7010,   // ERROR_LOCAL_LOCK_RETRY
        ]);
    }
}
