<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Support\PackageServiceProvider;
use RefactorCircus\PennantPlus\Domains\DomainServiceProvider;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\Mcp\PennantPlusServer;

class PennantPlusServiceProvider extends PackageServiceProvider
{
    /**
     * Feature flags have no models, so no `authorization` default: calls are
     * checked against the optional `pennantplus.ability` Gate ability instead.
     */
    protected function definition(): Package
    {
        return Package::make('pennantplus', __NAMESPACE__)
            ->label('PennantPlus')
            ->server(PennantPlusServer::class)
            // The history of flag changes is guarded like the rest of the API.
            ->authorizeHistory(fn (?Authenticatable $user): bool => $this->app->make(FeatureFlagManager::class)->allowsManagement($user));
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pennantplus.php', 'pennantplus');

        $this->registerPackage();

        $this->app->register(DomainServiceProvider::class);
    }

    public function boot(): void
    {
        $this->registerMcpServer();

        $this->loadHistoryRoutes();

        // A missing scope model answers the API with a plain JSON 404.
        $this->registerNotFoundResponses();

        // Cortex is optional: agents get the PennantPlus tools only when it is loaded.
        $this->registerCortex();

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'pennantplus');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/pennantplus.php' => config_path('pennantplus.php'),
        ], ['pennantplus', 'pennantplus-config']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/pennantplus'),
        ], ['pennantplus', 'pennantplus-lang']);

        // Optional indexes on Pennant's own table: never run unless published,
        // and kept out of the `pennantplus` tag so publishing everything
        // does not add them unasked.
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'pennantplus-migrations');
    }
}
