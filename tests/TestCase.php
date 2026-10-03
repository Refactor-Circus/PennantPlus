<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JayI\Atrium\AtriumServiceProvider;
use JayI\PennantPlus\Atrium\PennantPlugin;
use JayI\PennantPlus\PennantPlusServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Laravel\Pennant\PennantServiceProvider;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;
    use WithLaravelMigrations;

    protected function getPackageProviders($app): array
    {
        return [
            PennantServiceProvider::class,
            McpServiceProvider::class,
            AtriumServiceProvider::class,
            PennantPlusServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/laravel/pennant/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        // jayi/cortex is a dev dependency, so Atrium discovers its plugin here
        // without its migrations; its navigation would query missing tables.
        $app['config']->set('atrium.disabled', ['cortex']);

        tap($app->make(Repository::class), function (Repository $config): void {
            $config->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

            $config->set('database.default', 'testing');
            $config->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);

            // Atrium discovers the plugin from composer.json in a real
            // application; the package's own test app has to name it.
            $config->set('atrium.plugins', [PennantPlugin::class]);
        });
    }
}
