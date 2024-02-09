<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Console\Concerns;

trait CommandNameSpace
{
    protected function postfixCommandNamespace(string $command): string
    {
        if (config('arangodb.console_command_namespace') === '') {
            return $command;
        }
        return $command.'.'.config('arangodb.console_command_namespace');
    }

    protected function prefixCommandNamespace(string $command): string
    {
        if (config('arangodb.console_command_namespace') === '') {
            return $command;
        }
        return config('arangodb.console_command_namespace').':'.$command;
    }
}