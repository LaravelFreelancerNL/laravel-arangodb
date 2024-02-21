<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent;

use Illuminate\Support\ServiceProvider;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Schema\Grammar as SchemaGrammar;

class AranguentServiceProvider extends ServiceProvider
{
    /**
     * Components to register on the provider.
     *
     * @var array<string>
     */
    protected $components = [
        'Migration',
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (isset($this->app['db'])) {
            Model::setConnectionResolver($this->app['db']);
        }

        if (isset($this->app['events'])) {
            Model::setEventDispatcher($this->app['events']);
        }

        $this->publishes([
            __DIR__ . '/../config/arangodb.php' => config_path('arangodb.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/arangodb.php',
            'arangodb'
        );

        $this->app->singleton(\Illuminate\Database\Migrations\Migrator::class, function ($app) {
            return $app['migrator'];
        });

        $this->app->resolving(
            'db',
            function ($db) {
                $db->extend(
                    'arangodb',
                    function ($config, $name) {
                        $config['name'] = $name;
                        $connection = new Connection($config);
                        $connection->setSchemaGrammar(new SchemaGrammar());

                        return $connection;
                    }
                );
            }
        );

        $this->app->resolving(
            function () {
                if (class_exists('Illuminate\Foundation\AliasLoader')) {
                    $loader = \Illuminate\Foundation\AliasLoader::getInstance();
                    $loader->alias('Eloquent', 'LaravelFreelancerNL\Aranguent\Eloquent\Model');
                }
            }
        );

        $this->app->register('LaravelFreelancerNL\Aranguent\Providers\MigrationServiceProvider');
        $this->app->register('LaravelFreelancerNL\Aranguent\Providers\CommandServiceProvider');
    }
}
