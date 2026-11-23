<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Tests;

use Laravel\Ai\AiServiceProvider;
use RefactorCircus\Cortex\CortexServiceProvider;

/**
 * The package with Cortex installed and loaded.
 */
abstract class CortexTestCase extends TestCase
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AiServiceProvider::class,
            ...parent::getPackageProviders($app),
            CortexServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('cortex.cache.store', 'array');

        // Cortex (a dev dependency) pulls in Atrium, whose provider this
        // suite does not load: keep Cortex off the Atrium dashboard, so it
        // boots with its JSON API, MCP server and tool registry only.
        $app['config']->set('cortex.ui', ['enabled' => false]);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/refactor-circus/cortex/database/migrations');
    }
}
