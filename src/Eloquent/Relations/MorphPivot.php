<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\MorphPivot as IlluminateMorphPivot;
use LaravelFreelancerNL\Aranguent\Eloquent\Concerns\IsAranguentModel;

class MorphPivot extends IlluminateMorphPivot
{
    use IsAranguentModel;

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';
}
