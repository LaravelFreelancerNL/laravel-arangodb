<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\MorphToMany as IlluminateMorphToMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\InteractsWithPivotTable;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns\IsAranguentRelation;

class MorphToMany extends IlluminateMorphToMany
{
    use IsAranguentRelation;
    use InteractsWithPivotTable;
}
