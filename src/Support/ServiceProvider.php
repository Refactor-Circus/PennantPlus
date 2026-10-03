<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Base class for the PennantPlus domain service providers.
 *
 * Every management API route shares one group - the configured prefix and
 * middleware, and the `pennantplus.` name prefix - so each domain loads its
 * routes file through `loadApiRoutesFrom()` rather than building the group
 * itself.
 */
abstract class ServiceProvider extends BaseServiceProvider
{
    /**
     * Load a routes file inside the management API's route group, when the
     * API is enabled.
     */
    protected function loadApiRoutesFrom(string $path): void
    {
        $config = $this->app->make(Repository::class);

        if ($config->get('pennantplus.routes.enabled') !== true || $this->routesAreCached()) {
            return;
        }

        /** @var string $prefix */
        $prefix = $config->get('pennantplus.routes.prefix');

        /** @var array<int, string> $middleware */
        $middleware = $config->get('pennantplus.routes.middleware');

        Route::prefix($prefix)->middleware($middleware)->name('pennantplus.')->group($path);
    }

    protected function routesAreCached(): bool
    {
        return $this->app instanceof CachesRoutes && $this->app->routesAreCached();
    }
}
