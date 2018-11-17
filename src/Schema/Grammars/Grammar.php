<?php

namespace LaravelFreelancerNL\Aranguent\Schema\Grammars;

use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Grammars\Grammar as IlluminateGrammar;

class Grammar extends IlluminateGrammar
{
    /**
     * ArangoDB handles transactions itself. However most schema actions
     * are not done through queries but rather through commands.
     * So we just run schema actions sequentially.
     *
     * @var bool
     */
    protected $transactions = false;

    /**
     * Compile AQL to check if an attribute is in use within a document in the collection.
     * If multiple attributes are given then all must be set in one document.
     * @param string $collection
     * @param Fluent $command
     * @return Fluent
     */
    public function compileHasAttribute($collection, Fluent $command)
    {
        $filter = [];
        if (is_string($command->attribute)) {
            $command->attribute = [$command->attribute];
        }

        foreach ($command->attribute as $attribute) {
            $bindVar = $this->wrapBindVar($attribute);
            $bindings[] = $bindVar;
            $filter[] = '@' . $bindVar . ' != null';
        }

        $filter = implode(' && ', $filter);

        $query = "FOR document IN @@collection\n";
        $query .= "    FILTER $filter\n";
        $query .= "    LIMIT 1\n";
        $query .= "    RETURN true";

        $bindings['@collection'] = $collection;

        $aql['query'] = $query;
        $aql['bindings'] = $bindings;
        $collections['read'] = $collection;

        $command->aql = $aql;
        $command->collections = $collections;

        return $command;
    }

    /**
     * Compile AQL to rename an attribute, if the new name isn't already in use.
     *
     * @param string $collection
     * @param Fluent $command
     * @return Fluent
     */
    public function compileRenameAttribute($collection, Fluent $command)
    {
        $bindings['@collection'] = $collection;

        $bindings['from'] = $this->wrapBindVar($command->to);
        $bindings['to'] = $this->wrapBindVar($command->from);

        $query  = "
FOR document IN @@collection
    FILTER @from != null && @to == null
    UPDATE document WITH {
        @from: null,
        @to: document.@from
} IN @@collection OPTIONS { keepNull: false }
";

        $aql['query'] = $query;
        $aql['bindings'] = $bindings;
        $collections['write'] = $collection;
        $collections['read'] = $collection;

        $command->aql = $aql;
        $command->collections = $collections;

        return $command;
    }

    /**
     * Compile AQL to drop one or more attributes.
     *
     * @param string $collection
     * @param Fluent $command
     * @return Fluent
     */
    public function compileDropAttribute($collection, Fluent $command)
    {
        $filter = [];
        if (is_string($command->attributes)) {
            $command->attributes = [$command->attributes];
        }

        foreach ($command->attributes as $attribute) {
            $bindVar = $this->wrapBindVar($attribute);
            $bindings[] = $bindVar;
            $filter[] = '@' . $bindVar . ' != null';
        }
        $filter = implode(' || ', $filter);

        $query = "FOR document IN @@collection\n";
        $query .= " FILTER $filter\n";
        $query .= " UPDATE document WITH {\n";
        foreach ($bindings as $bindVar) {
            $query .= "     @$bindVar: null,\n";
        }
        $query .= '} IN @@collection OPTIONS { keepNull: false }';
        $bindings['@collection'] = $collection;


        $aql['query'] = $query;
        $aql['bindings'] = $bindings;
        $collections['read'] = $collection;
        $collections['write'] = $collection;

        $command->aql = $aql;
        $command->collections = $collections;

        return $command;
    }

    /**
     * Prepare a bindVar for inclusion in an AQL query.
     *
     * @param $attribute
     * @return string
     */
    public function wrapBindVar($attribute)
    {
        $attribute = trim($attribute);
        if (strpos($attribute, '@') === 0) {
            $attribute = "`$attribute`";
        }

        return $attribute;
    }
}
