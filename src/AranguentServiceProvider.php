<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent;

use Illuminate\Database\Migrations\MigrationCreator as IlluminateMigrationCreator;
use Illuminate\Support\ServiceProvider;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Migrations\MigrationCreator;
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
        /**
         * When the MigrationCreator complains about an unset $customStubPath
         * we resolve it here
         */
        $this->app->when(MigrationCreator::class)
            ->needs('$customStubPath')
            ->give(function () {
                return __DIR__.'/../stubs';
            });
        $this->app->when(IlluminateMigrationCreator::class)
            ->needs('$customStubPath')
            ->give(function () {
                return __DIR__.'/../stubs';
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

        $this->app->register('LaravelFreelancerNL\Aranguent\Providers\CommandServiceProvider');
    }
}
