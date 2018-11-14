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
     *
     * @param string $collection
     * @param Fluent $command
     * @return Fluent
     */
    public function compileHasAttribute($collection, Fluent $command)
    {
        $bindings['@collection'] = $collection;
        $filter = [];

        $i = 1;
        foreach ($command->attributes as $attribute) {
            $bindVar = 'attribute_'.$i;
            $bindings[$bindVar] = $attribute;
            $filter[] = '@'.$bindVar.' != null';
            $i++;
        }
        $filter = implode(' && ', $filter);

        $query = "FOR document IN @@collection\n";
        $query .= " FILTER $filter\n";
        $query .= " LIMIT 1\n";
        $query .= " RETURN true\n";

        $command->aql['query'] = $query;
        $command->aql['bindings'] = $bindings;
        $command->collections['write'] = $collection;
        $command->collections['read'] = $collection;

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

        $bindings['from'] = $command->attributes['to'];
        $bindings['to'] = $command->attributes['from'];

        $query = "FOR document IN @@collection\n";
        $query .= " FILTER @from != null && @to == null\n";
        $query .= " UPDATE document WITH {\n";
        foreach ($bindings as $bindVar => $value) {
            $query .= "     @from: null,\n";
            $query .= "     @to: document.@from\n";
        }
        $query .= '} IN @@collection OPTIONS { keepNull: false }';

        $command->aql['query'] = $query;
        $command->aql['bindings'] = $bindings;
        $command->collections['write'] = $collection;
        $command->collections['read'] = $collection;

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
        $bindings['@collection'] = $collection;
        $filter = [];
        if (is_string($command->attributes)) {
            $command->attributes = [$command->attributes];
        }

        $i = 1;
        foreach ($command->attributes as $attribute) {
            $bindVar = '@attribute_'.$i;
            $bindings[$bindVar] = $attribute;
            $filter[] = $bindVar.' != null';
            $i++;
        }
        $filter = implode(' || ', $filter);

        $query = "FOR document IN @@collection\n";
        $query .= " FILTER $filter\n";
        $query .= " UPDATE document WITH {\n";
        foreach ($bindings as $bindVar => $value) {
            $query .= "     $bindVar: null,\n";
        }
        $query .= '} IN @@collection OPTIONS { keepNull: false }';

        $command->aql['query'] = $query;
        $command->aql['bindings'] = $bindings;
        $command->collections['read'] = $collection;

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
