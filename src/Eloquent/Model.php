<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent;

use Illuminate\Database\Eloquent\Model as IlluminateModel;
use LaravelFreelancerNL\Aranguent\Eloquent\Concerns\IsAranguentModel;

abstract class Model extends IlluminateModel
{
    use IsAranguentModel;

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';
}
