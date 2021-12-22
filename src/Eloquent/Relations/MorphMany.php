<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\MorphMany as IlluminateMorphMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class MorphMany extends IlluminateMorphMany
{
    use IsAranguentRelation;
}
