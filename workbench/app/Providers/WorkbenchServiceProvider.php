<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use JayI\PennantPlus\Atrium\PennantPlugin;
use Laravel\Pennant\Feature;
use Workbench\App\Models\User;

/**
 * Turns the workbench into a demo app: PennantPlus's layered store on the
 * workbench User, with the Atrium Feature flags page open to the signed-in
 * user.
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
            // Atrium discovers plugins from vendor/composer/installed.json,
            // which never lists the package being developed.
            'atrium.plugins' => [PennantPlugin::class],
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // The demo dashboard is open to every signed-in workbench user.
        Gate::define('viewAtrium', fn (?User $user): bool => $user !== null);

        // Closure features, defined the way an application would.
        Feature::define('dark-mode', fn (mixed $scope): bool => false);
        Feature::define('checkout-button-color', fn (mixed $scope): string => 'blue');
    }
}
