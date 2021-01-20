<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\HasOneThrough as IlluminateHasOneThrough;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class HasOneThrough extends IlluminateHasOneThrough
{
    use IsAranguentRelation;
}