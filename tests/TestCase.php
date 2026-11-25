<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\McpServiceProvider;
use Laravel\Pennant\PennantServiceProvider;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\PennantPlus\PennantPlusServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;
    use WithLaravelMigrations;

    protected function getPackageProviders($app): array
    {
        return [
            PennantServiceProvider::class,
            McpServiceProvider::class,
            PennantPlusServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/laravel/pennant/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        tap($app->make(Repository::class), function (Repository $config): void {
            $config->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

            $config->set('database.default', 'testing');
            $config->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        });
    }
}
