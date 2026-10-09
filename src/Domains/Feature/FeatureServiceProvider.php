<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Laravel\Pennant\Feature;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureGate;
use RefactorCircus\PennantPlus\Domains\Feature\Support\GlobalAwareDatabaseDriver;

class FeatureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
