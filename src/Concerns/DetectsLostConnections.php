<?php

namespace LaravelFreelancerNL\Aranguent\Concerns;

use Throwable;

trait DetectsLostConnections
{
    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function causedByLostConnection(Throwable $e)
    {
        // https://www.arangodb.com/docs/stable/appendix-error-codes.html
        // 30 - ERROR_SHUTTING_DOWN
        // 500 - ERROR_HTTP_SERVER_ERROR
        // 503 - ERROR_HTTP_SERVICE_UNAVAILABLE
        // 504 - ERROR_HTTP_GATEWAY_TIMEOUT
        // 1302 - ERROR_ARANGO_TRY_AGAIN
        // 1303 - ERROR_ARANGO_BUSY
        // 1464 - ERROR_CLUSTER_SHARD_GONE
        // 1465 - ERROR_CLUSTER_CONNECTION_LOST

        $code = $e->getCode();

        return in_array($code, [
            30,
            500,
            503,
            504,
            1302,
            1303,
            1464,
            1465,
        ]);
    }
}
