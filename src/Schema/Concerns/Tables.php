<?php

namespace LaravelFreelancerNL\Aranguent\Schema\Concerns;

use Illuminate\Support\Fluent;

trait Tables
{
    /**
     * Indicate that the table needs to be created.
     *
     * @param array $options
     *
     * @return Fluent
     */
    public function create($options = [])
    {
        $parameters = [];
        $parameters['options'] = $options;
        $parameters['explanation'] = "Create '{$this->table}' table.";
        $parameters['handler'] = 'table';

        return $this->addCommand('create', $parameters);
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * @return bool
     */
    protected function creating()
    {
        return collect($this->commands)->contains(function ($command) {
            return $command->name === 'create';
        });
    }

    public function executeCreateCommand($command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery('/* ' . $command->explanation . " */\n", []);

            return;
        }
        $options = $command->options;
        if ($this->temporary === true) {
            $options['isVolatile'] = true;
        }
        if ($this->autoIncrement === true) {
            $options['keyOptions']['autoincrement'] = true;
        }

        if (! $this->schemaManager->hasCollection($this->table)) {
            $this->schemaManager->createCollection($this->table, $options);
        }
    }

    /**
     * Alias for getCollection.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Rename the table to a given name.
     *
     * @param string $to
     *
     * @return Fluent
     */
    public function rename($to)
    {
        return $this->addCommand('rename', compact('to'));
    }

    /**
     * Indicate that the table should be dropped.
     *
     * @return Fluent
     */
    public function drop()
    {
        $parameters = [];
        $parameters['explanation'] = "Drop the '{$this->table}' table.";
        $parameters['handler'] = 'table';

        return $this->addCommand('drop', $parameters);
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * @return Fluent
     */
    public function dropIfExists()
    {
        $parameters = [];
        $parameters['explanation'] = "Drop the '{$this->table}' table.";
        $parameters['handler'] = 'table';

        return $this->addCommand('dropIfExists');
    }
}
