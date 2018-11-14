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
        // https://docs.arangodb.com/3.3/Manual/Appendix/ErrorCodes.html
        // 30 - ERROR_SHUTTING_DOWN
        // 401 - ERROR_HTTP_UNAUTHORIZED
        // 403 - ERROR_HTTP_FORBIDDEN
        // 404 - ERROR_HTTP_NOT_FOUND
        // 405 - ERROR_HTTP_METHOD_NOT_ALLOWED
        // 406 - ERROR_HTTP_NOT_ACCEPTABLE
        // 412 - ERROR_HTTP_PRECONDITION_FAILED
        // 500 - ERROR_HTTP_SERVER_ERROR
        // 503 - ERROR_HTTP_SERVICE_UNAVAILABLE
        // 504 - ERROR_HTTP_GATEWAY_TIMEOUT
        // 1464 - ERROR_CLUSTER_SHARD_GONE
        // 1465 - ERROR_CLUSTER_CONNECTION_LOST

        $code = $e->code();

        return in_array($code, [
            30,
            401,
            403,
            404,
            405,
            406,
            412,
            500,
            503,
            504,
            1464,
            1465
        ]);
    }
}
