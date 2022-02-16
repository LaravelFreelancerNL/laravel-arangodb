<?php

namespace Tests\Setup;

use Illuminate\Support\ServiceProvider;

class AranguentTestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * We set the connection config here to allow testbench access outside of running tests.
         */
        TestConfig::set($this->app);
    }
}
