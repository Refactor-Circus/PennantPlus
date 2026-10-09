<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains;

use Illuminate\Support\ServiceProvider;
use RefactorCircus\PennantPlus\Domains\Feature\FeatureServiceProvider;
use RefactorCircus\PennantPlus\Domains\Scope\ScopeServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * The domain service providers.
     *
     * @var array<int, class-string<ServiceProvider>>
     */
    private array $providers = [
        FeatureServiceProvider::class,
        ScopeServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }
}
