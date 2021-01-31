<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as IlluminateBelongsToMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class BelongsToMany extends IlluminateBelongsToMany
{
    use IsAranguentRelation;
}
