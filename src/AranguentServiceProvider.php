<?php

namespace LaravelFreelancerNL\Aranguent;

use Illuminate\Support\ServiceProvider;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Schema\Grammar as SchemaGrammar;

class AranguentServiceProvider extends ServiceProvider
{
    /**
     * Components to register on the provider.
     *
     * @var array
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
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Add database driver.
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
        $this->app->register('LaravelFreelancerNL\Aranguent\Providers\CommandServiceProvider');
    }
}
