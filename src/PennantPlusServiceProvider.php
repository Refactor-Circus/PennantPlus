<?php

declare(strict_types=1);

namespace JayI\PennantPlus;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use JayI\Atrium\Domains\Access\Services\Gatekeeper;
use JayI\Atrium\Domains\Plugins\Services\PluginRegistry;
use JayI\Atrium\Support\StyleRegistry;
use JayI\Foundation\Packages\Package;
use JayI\Foundation\Support\PackageServiceProvider;
use JayI\PennantPlus\Atrium\FeatureFlagAccess;
use JayI\PennantPlus\Domains\DomainServiceProvider;
use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use JayI\PennantPlus\Domains\Feature\Services\FeatureGate;
use JayI\PennantPlus\Mcp\PennantPlusServer;

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

        $this->registerAtriumFeatureResolver();

        $this->registerAtriumBladeConditional();

        // Cortex is optional: agents get the PennantPlus tools only when it is loaded.
        $this->registerCortex();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pennantplus');

        // Utilities the Atrium screens use that Atrium's stylesheet lacks.
        $this->callAfterResolving(StyleRegistry::class, fn (StyleRegistry $styles): StyleRegistry => $styles->css(
            (string) file_get_contents(__DIR__.'/../resources/css/atrium.css'),
            'pennantplus',
        ));

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
     * Let the feature gate decide Atrium's features, when Atrium is
     * installed. Waiting for Atrium's Gatekeeper keeps provider order from
     * mattering, and an application registering its own resolver later
     * still replaces this one.
     */
    private function registerAtriumFeatureResolver(): void
    {
        if (! class_exists(Gatekeeper::class)) {
            return;
        }

        if ($this->app->make(Repository::class)->get('pennantplus.atrium.resolve_features') !== true) {
            return;
        }

        $this->callAfterResolving(Gatekeeper::class, function (Gatekeeper $gatekeeper): void {
            $gatekeeper->resolveFeaturesUsing(function (string $feature, Request $request): bool {
                $user = $request->user();

                return $this->app->make(FeatureGate::class)->allows($feature, $user instanceof Authenticatable ? $user : null);
            });
        });
    }

    /**
     * `@pennantplusManages` shows a control on the Feature flags screen only
     * when the signed-in user may manage feature flags, when Atrium is
     * installed.
     */
    private function registerAtriumBladeConditional(): void
    {
        if (! class_exists(PluginRegistry::class)) {
            return;
        }

        Blade::if('pennantplusManages', fn (): bool => FeatureFlagAccess::allows());
    }
}
