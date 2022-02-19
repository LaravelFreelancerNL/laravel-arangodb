<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\Pivot as IlluminatePivot;
use LaravelFreelancerNL\Aranguent\Eloquent\Concerns\IsAranguentModel;

class Pivot extends IlluminatePivot
{
    use IsAranguentModel;

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';
}
