<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\HasManyThrough as IlluminateHasManyThrough;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class HasManyThrough extends IlluminateHasManyThrough
{
    use IsAranguentRelation;
}
