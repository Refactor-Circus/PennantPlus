<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains;

use Illuminate\Support\ServiceProvider;
use JayI\PennantPlus\Domains\Feature\FeatureServiceProvider;
use JayI\PennantPlus\Domains\Scope\ScopeServiceProvider;

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
