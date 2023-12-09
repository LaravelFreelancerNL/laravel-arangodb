<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Enums;

enum VariablePosition: string
{
    case preIterations = 'preIterationVariables';
    case postIterations = 'postIterationVariables';
}
