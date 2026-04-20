<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Override createApplication to force SQLite in-memory BEFORE RefreshDatabase runs.
     *
     * In a Dockerized environment, OS env vars (DB_CONNECTION=pgsql, DB_HOST=postgres, ...)
     * take precedence over Dotenv and phpunit.xml because Laravel uses createImmutable().
     * Overriding config here ensures RefreshDatabase uses SQLite, not the real PostgreSQL database.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.foreign_key_constraints', true);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Double-check guard: fail loudly if something bypassed the createApplication override.
        $driver = DB::getDriverName();
        if ($driver !== 'sqlite') {
            throw new RuntimeException(
                "Tests are running against a non-SQLite database (driver: {$driver}). "
                .'This is dangerous — aborting to protect production data.'
            );
        }
    }
}
