<?php

namespace Workbench\App\Providers;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Workbench\App\Http\Middleware\SignInWorkbenchUser;
use Workbench\App\Models\User;

/**
 * Turns the workbench into a demo app: PennantPlus's layered store on the
 * workbench User, with the JSON API (start at /pennantplus/features) and
 * the MCP server managing its feature flags. It also configures Scramble,
 * which exports the JSON API's OpenAPI document.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        config([
            'auth.providers.users.model' => User::class,
            // Layered global + per-user values, as in a real application.
            'pennant.default' => 'pennantplus',
            'pennant.stores.pennantplus' => [
                'driver' => 'pennantplus',
                'connection' => null,
                'table' => 'features',
            ],
            'pennantplus.features' => [dirname(__DIR__).'/Features'],
            'pennantplus.scopes' => [
                User::class => [
                    'label' => 'Users',
                    'search' => ['name', 'email'],
                    'title' => 'name',
                ],
            ],
            // The OpenAPI document (openapi.json): `scramble:export` and
            // tests/OpenApi both read this one configuration, so the export
            // holds only the JSON API and nothing machine-specific.
            'scramble.api_path' => 'pennantplus',
            'scramble.ui.title' => 'PennantPlus',
            'scramble.info.version' => '2.0.0',
            'scramble.servers' => ['PennantPlus API' => 'http://localhost/pennantplus'],
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Keep the workbench user signed in whatever URL is opened first.
        $this->callAfterResolving(HttpKernel::class, function (HttpKernel $kernel): void {
            if ($kernel instanceof Kernel) {
                $kernel->appendMiddlewareToGroup('web', SignInWorkbenchUser::class);
            }
        });

        // Closure features, defined the way an application would.
        Feature::define('dark-mode', fn (mixed $scope): bool => false);
        Feature::define('checkout-button-color', fn (mixed $scope): string => 'blue');
    }
}
