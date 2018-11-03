<?php

namespace LaravelFreelancerNL\Aranguent;

use Illuminate\Support\ServiceProvider;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Schema\Grammars\AqlGrammar;

class AranguentServiceProvider extends ServiceProvider
{

    /**
     * Components to register on the provider.
     *
     * @var array
     */
    protected $components = array(
        'Migration'
    );

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
        $this->app->resolving('db', function ($db) {
            $db->extend('arangodb', function ($config, $name) {
                $config['name'] = $name;
                $connection = new Connection($config);
                $connection->setSchemaGrammar(new AqlGrammar);
                return $connection ;
            });
        });

        $this->app->register('LaravelFreelancerNL\Aranguent\MigrationServiceProvider');
    }
}
