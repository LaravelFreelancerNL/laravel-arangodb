<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany as IlluminateHasMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\HasOneOrMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class HasMany extends IlluminateHasMany
{
    use IsAranguentRelation;
    use HasOneOrMany;
}
