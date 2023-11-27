<?php

namespace Tests;

use Illuminate\Contracts\Foundation\Application;
use Spatie\Ray\Settings\Settings;

use function Orchestra\Testbench\after_resolving;

/**
 * @internal
 *
 * @phpstan-type TLaravel \Illuminate\Contracts\Foundation\Application
 */
class BootstrapTestbench
{
    /**
     * Bootstrap the given application.
     *
     * @param  TLaravel  $app
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function bootstrap(Application $app): void
    {
        after_resolving($app, Settings::class, static function ($settings, $app) {
            $config = $app->make('config');

            $config->set('database.connections.arangodb', [
                'name'                                      => 'arangodb',
                'driver'                                    => 'arangodb',
                'endpoint'                                  => env('DB_ENDPOINT', 'http://localhost:8529'),
                'username'                                  => env('DB_USERNAME', 'root'),
                'password'                                  => env('DB_PASSWORD', null),
                'database'                                  => env('DB_DATABASE', 'aranguent__test'),
            ]);
            $config->set('database.default', 'arangodb');
        });
    }
}
