<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Schema\Concerns;

use Illuminate\Support\Fluent;

trait Tables
{
    /**
     * Indicate that the table needs to be created.
     *
     * @param  array  $options
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

    public function executeCreateCommand($command)
    {
        if ($this->connection->pretending()) {
            $this->connection->logQuery('/* ' . $command->explanation . " */\n", []);

            return;
        }
        $options = $command->options;

        if ($this->keyGenerator !== 'traditional') {
            $options['keyOptions']['type'] = $this->keyGenerator;
        }

        if ($this->keyGenerator === 'autoincrement' && $this->incrementOffset !== 0) {
            $options['keyOptions']['offset'] = $this->incrementOffset;
        }

        if (!$this->schemaManager->hasCollection($this->table)) {
            $this->schemaManager->createCollection($this->table, $options);
        }
    }
}
