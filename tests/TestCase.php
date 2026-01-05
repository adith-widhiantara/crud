<?php

namespace Adithwidhiantara\Crud\Tests;

use Adithwidhiantara\Crud\CrudServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Opsional: Setup database jika nanti butuh testing database sqlite in-memory
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        // Pastikan folder controller ada di lingkungan testbench
        if (! is_dir(app_path('Http/Controllers'))) {
            mkdir(app_path('Http/Controllers'), 0777, true);
        }
    }

    /**
     * Load Package Service Provider.
     * Ini krusial agar CrudServiceProvider jalan.
     */
    protected function getPackageProviders($app)
    {
        return [
            CrudServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default config jika perlu
        // $app['config']->set('crud.prefix', 'api');
    }
}
