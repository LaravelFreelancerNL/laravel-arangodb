<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Exceptions;

class NoArangoClientException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'No ArangoDB Client is set.';
}
