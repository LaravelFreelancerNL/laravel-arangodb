<?php
namespace LaravelFreelancerNL\Aranguent\Schema\Grammars;

use Illuminate\Support\Fluent;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;

class AqlGrammar extends Grammar
{

    /**
     * ArangoDB supports transactions for AQL migrations(?)
     * FIXME: This needs to be checked
     *
     * @return bool
     */
    public function supportsSchemaTransactions()
    {
        return true;
    }

    /**
     * One of the few migrations that can and has to be done through AQL instead of collectionHandler methods
     *
     * @param Blueprint $blueprint
     * @param Fluent $command
     * @return string
     */
    public function compileRenameAttribute(Blueprint $blueprint, Fluent $command)
    {
        return '';
    }

    public function compileRenameColumn(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return $this->compileRenameAttribute($this, $blueprint, $command);
    }
}