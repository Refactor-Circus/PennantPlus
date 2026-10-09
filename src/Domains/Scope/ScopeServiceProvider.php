<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope;

use RefactorCircus\Foundation\Support\ServiceProvider;

class ScopeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
