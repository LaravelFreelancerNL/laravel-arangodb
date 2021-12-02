<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo as IlluminateBelongsTo;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class BelongsTo extends IlluminateBelongsTo
{
    use IsAranguentRelation;
}
