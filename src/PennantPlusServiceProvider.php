<?php

declare(strict_types=1);

namespace JayI\PennantPlus;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use JayI\PennantPlus\Cortex\CortexIntegration;
use JayI\PennantPlus\Drivers\GlobalAwareDatabaseDriver;
use JayI\PennantPlus\Mcp\PennantPlusServer;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Pennant\Feature;

class PennantPlusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pennantplus.php', 'pennantplus');

        $this->app->singleton(FeatureGate::class);
        $this->app->singleton(FeatureFlagManager::class);
    }

    public function boot(): void
    {
        Feature::extend('pennantplus', fn (Application $app, array $config): GlobalAwareDatabaseDriver => new GlobalAwareDatabaseDriver(
            $app->make(DatabaseManager::class),
            $app->make(Dispatcher::class),
            $app->make(Repository::class),
            $config,
        ));

        if ($this->app->make(Repository::class)->get('pennantplus.routes.enabled') === true) {
            $this->loadRoutesFrom(__DIR__.'/../routes/pennantplus.php');
        }

        $this->registerMcpServer();

        // Cortex is optional: agents get the PennantPlus tools only when it is loaded.
        $this->app->make(CortexIntegration::class)->register();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pennantplus');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'pennantplus');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/pennantplus.php' => config_path('pennantplus.php'),
        ], ['pennantplus', 'pennantplus-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/pennantplus'),
        ], ['pennantplus', 'pennantplus-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/pennantplus'),
        ], ['pennantplus', 'pennantplus-lang']);
    }

    /**
     * Register the MCP server transports enabled in the config.
     */
    private function registerMcpServer(): void
    {
        $config = $this->app->make(Repository::class);

        if ($config->get('pennantplus.mcp.web.enabled') === true) {
            /** @var array<int, string> $middleware */
            $middleware = $config->get('pennantplus.mcp.web.middleware', []);

            Mcp::web((string) $config->get('pennantplus.mcp.web.route'), PennantPlusServer::class)
                ->middleware($middleware);
        }

        if ($config->get('pennantplus.mcp.local.enabled') === true) {
            Mcp::local((string) $config->get('pennantplus.mcp.local.handle'), PennantPlusServer::class);
        }
    }
}
