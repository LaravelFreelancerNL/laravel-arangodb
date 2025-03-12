<?php

namespace LaravelFreelancerNL\Aranguent\Console;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\TableCommand as IlluminateTableCommand;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Number;
use LaravelFreelancerNL\Aranguent\Connection;

use function Laravel\Prompts\select;

class TableCommand extends IlluminateTableCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:table
                            {table? : The name of the table}
                            {--database= : The database connection}
                            {--system= : Include system tables (ArangoDB)}
                            {--json : Output the table information as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display information about the given database table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ConnectionResolverInterface $connections)
    {
        $connection = $connections->connection($this->input->getOption('database'));

        if (! $connection instanceof Connection) {
            return parent::handle($connections);
        }

        $schema = $connection->getSchemaBuilder();

        $tables = collect(
            ($this->input->getOption('system'))
                ? $schema->getAllTables()
                : $schema->getTables(),
        )
            ->keyBy(fn($table) => (string) $table['name'])
            ->all();

        $tableName = (string) $this->argument('table') ?: select(
            'Which table would you like to inspect?',
            array_keys($tables),
        );

        $table = $schema->getTable((string) $tableName);

        if (! $table) {
            $this->components->warn("Table [{$tableName}] doesn't exist.");

            return 1;
        }

        [$columns, $indexes] = $connection->withoutTablePrefix(function ($connection) use ($table) {
            $schema = $connection->getSchemaBuilder();
            $tableName = $table['name'];

            return [
                $this->columns($schema, $tableName),
                $this->indexes($schema, $tableName),
            ];
        });



        $data = [
            'table' => $table,
            'columns' => $columns,
            'indexes' => $indexes,
        ];

        $this->display($data);

        return 0;
    }

    /**
     * Get the information regarding the table's columns.
     *
     * @param  \Illuminate\Database\Schema\Builder  $schema
     * @param  string  $table
     * @return \Illuminate\Support\Collection
     */
    protected function columns(Builder $schema, string $table)
    {
        return collect($schema->getColumns($table));
    }

    /**
     * Get the information regarding the table's indexes.
     *
     * @param  \Illuminate\Database\Schema\Builder  $schema
     * @param  string  $table
     * @return \Illuminate\Support\Collection
     */
    protected function indexes(Builder $schema, string $table)
    {
        return collect($schema->getIndexes($table))->map(fn($index) => [
            'name' => (string) $index['name'],
            'columns' => collect((array) $index['fields']),
            'attributes' => $this->getAttributesForIndex((array) $index),
        ]);
    }

    /**
     * Get the attributes for a table index.
     *
     * @param  array<mixed>  $index
     * @return \Illuminate\Support\Collection
     */
    protected function getAttributesForIndex($index)
    {
        return collect(
            array_filter([
                'sparse' => $index['sparse'] ? 'sparse' : null,
                'unique' => $index['unique'] ? 'unique' : null,
                'type' => $index['type'],
            ]),
        )->filter();
    }

    /**
     * Render the table information.
     *
     * @param  mixed[]  $data
     * @return void
     */
    protected function display(array $data)
    {
        $this->option('json') ? $this->displayJson($data) : $this->displayForCli($data);
    }

    protected function displayLongStringValue(string $value): string
    {
        if (strlen($value) < 136) {
            return $value;
        }
        return substr($value, 0, 133) . '...';
    }

    /**
     * Render the table information formatted for the CLI.
     *
     * @param  mixed[] $data
     * @return void
     */
    protected function displayForCli(array $data)
    {
        [$table, $columns, $indexes ] = [
            $data['table'], $data['columns'], $data['indexes'],
        ];

        $this->newLine();

        $this->components->twoColumnDetail('<fg=green;options=bold>Table</>', '<fg=green;options=bold>' . $table['name'] . '</>');
        $this->components->twoColumnDetail('Type', ($table['type'] == 2) ? 'Vertex' : 'Edge');
        $this->components->twoColumnDetail('Status', $table['statusString']);
        $this->components->twoColumnDetail('User Keys Allowed', ($table['keyOptions']->allowUserKeys) ? 'Yes' : 'No');
        $this->components->twoColumnDetail('Key Type', $table['keyOptions']->type);
        $this->components->twoColumnDetail('Last Used Key', $table['keyOptions']->lastValue);
        $this->components->twoColumnDetail('Wait For Sync', ($table['waitForSync']) ? 'Yes' : 'No');
        $this->components->twoColumnDetail('Columns', $table['count']);
        $this->components->twoColumnDetail('Size Estimate', Number::fileSize($table['figures']->documentsSize, 2));

        $this->newLine();

        if ($columns->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Column</>', '<fg=green;options=bold>Type</>');

            $columns->each(function ($column) {
                $this->components->twoColumnDetail(
                    $column['name'],
                    implode(', ', $column['type']),
                );
            });
            $this->components->info('ArangoDB is schemaless by default. Hence, the column & types are a representation of current data within the table.');
        }

        $computedValues = collect((array) $table['computedValues']);
        if ($computedValues->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Computed Value</>', '<fg=green;options=bold>Expression</>');

            $computedValues->each(function ($value) {
                $this->components->twoColumnDetail(
                    $value->name,
                    $this->displayLongStringValue($value->expression),
                );
            });

            $this->newLine();
        }

        if ($indexes->isNotEmpty()) {
            $this->components->twoColumnDetail('<fg=green;options=bold>Index</>');

            $indexes->each(function ($index) {
                $this->components->twoColumnDetail(
                    $index['name'] . ' <fg=gray>' . $index['columns']->implode(', ') . '</>',
                    $index['attributes']->implode(', '),
                );
            });

            $this->newLine();
        }
    }
}
