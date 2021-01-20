<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\HasOne as IlluminateHasOne;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class HasOne extends IlluminateHasOne
{
    use IsAranguentRelation;
}