<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Scope;

use JayI\PennantPlus\Support\ServiceProvider;

class ScopeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
