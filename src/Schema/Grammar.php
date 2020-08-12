<?php

namespace LaravelFreelancerNL\Aranguent\Schema;

use Illuminate\Database\Schema\Grammars\Grammar as IlluminateGrammar;
use Illuminate\Support\Fluent;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

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
     * If multiple attributes are set then all must be set in one document.
     * @param string $collection
     * @param Fluent $command
     * @return Fluent
     */
    public function compileHasAttribute($collection, Fluent $command)
    {
        if (is_string($command->attribute)) {
            $command->attribute = [$command->attribute];
        }

        $filter = [];
        foreach ($command->attribute as $attribute) {
            $filter[] = ['doc.' . $attribute, '!=', 'null'];
        }

        $aqb = (new QueryBuilder())->for('doc', $collection)
            ->filter($filter)
            ->limit(1)
            ->return('true')
            ->get();

        $command->aqb = $aqb;

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

        $filter = [
            ['doc.' . $command->from, '!=', 'null'],
            ['doc.' . $command->to],
        ];

        $aqb = (new QueryBuilder())->for('doc', $collection)
            ->filter($filter)
            ->update(
                'doc',
                [
                    $command->from => 'null',
                    $command->to => 'doc.' . $command->from,
                ],
                $collection
            )
            ->options(['keepNull' => true])
            ->get();

        $command->aqb = $aqb;

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

        $data = [];
        foreach ($command->attributes as $attribute) {
            $filter[] = ['doc.' . $attribute, '!=', 'null', 'OR'];
            $data[$attribute] = 'null';
        }
        $aqb = (new QueryBuilder())->for('doc', $collection)
            ->filter($filter)
            ->update('doc', $data, $collection)
            ->options(['keepNull' => false])
            ->get();

        $command->aqb = $aqb;

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
