<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as IlluminateBelongsToMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\InteractsWithPivotTable;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class BelongsToMany extends IlluminateBelongsToMany
{
    use IsAranguentRelation;
    use InteractsWithPivotTable;
}
