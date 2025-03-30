<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;

trait GeneratesTableAlias
{
    public function generateTableAlias(string|Expression $table, string $postfix = 'Doc'): string
    {
        if (Str::startsWith($table, 'laravel_')) {
            return $table;
        }

        if ($table instanceof Expression) {
            return 'laravel_expression_' . spl_object_id($table);
        }

        return Str::camel(Str::singular($table)) . $postfix;
    }
}
