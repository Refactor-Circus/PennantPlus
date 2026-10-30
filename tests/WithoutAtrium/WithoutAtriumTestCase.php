<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Tests\WithoutAtrium;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\McpServiceProvider;
use Laravel\Pennant\PennantServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\PennantPlus\PennantPlusServiceProvider;

/**
 * The package with refactor-circus/atrium absent: no Atrium provider, and
 * (through tests/WithoutAtrium/bootstrap.php) no Atrium class to load.
 */
abstract class WithoutAtriumTestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * The MCP server's web route, turned on for the tools/list check.
     */
    public const string MCP_ROUTE = 'mcp/pennantplus';

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
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/vendor/laravel/pennant/database/migrations');
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

            // The whole `mcp` block, since the package config merges one level deep.
            $config->set('pennantplus.mcp', [
                'web' => ['enabled' => true, 'route' => self::MCP_ROUTE, 'middleware' => []],
                'local' => ['enabled' => false, 'handle' => 'pennantplus'],
            ]);
        });
    }
}
