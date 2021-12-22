<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\MorphTo as IlluminateMorphTo;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class MorphTo extends IlluminateMorphTo
{
    use IsAranguentRelation;
}
