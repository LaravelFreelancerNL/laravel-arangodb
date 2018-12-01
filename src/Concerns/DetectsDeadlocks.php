<?php

namespace LaravelFreelancerNL\Aranguent\Concerns;

use Exception;

trait DetectsDeadlocks
{
    /**
     * Determine if the given exception was caused by a deadlock.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function causedByDeadlock(Exception $e)
    {
        // https://docs.arangodb.com/3.3/Manual/Appendix/ErrorCodes.html
        // 18 ERROR_LOCK_TIMEOUT
        // 28 ERROR_LOCKED
        // 29 ERROR_DEADLOCK
        // 1200 - ERROR_ARANGO_CONFLICT (write-write conflict)
        // 1302 - ERROR_ARANGO_TRY_AGAIN
        // 1303 - ERROR_ARANGO_BUSY

        $code = $e->getServerCode();

        return in_array($code, [
            18,
            28,
            29,
            1302,
            1303,
        ]);
    }
}
