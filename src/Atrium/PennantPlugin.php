<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavGroup;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Plugins\Support\Plugin;
use RefactorCircus\Atrium\Support\Icons;
use RefactorCircus\PennantPlus\Atrium\Http\Controllers\FeatureFlagController;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Manages the feature flag values Pennant has stored.
 *
 * Atrium discovers it from composer.json when refactor-circus/atrium is installed. Hide
 * it like any other plugin by listing its `pennant` key under
 * `atrium.disabled`.
 */
class PennantPlugin extends Plugin
{
    public function key(): string
    {
        return 'pennant';
    }

    public function label(): string
    {
        return __('pennantplus::pennantplus.features');
    }

    /**
     * Checks `pennantplus.ability` when one is configured, on top of the
     * dashboard gate every Atrium route already enforces.
     */
    public function authorize(Request $request): bool
    {
        return app(FeatureFlagManager::class)->allowsManagement($request->user());
    }

    /**
     * The package's section in the sidebar rail: its icon and its place.
     */
    public function navigationGroups(): array
    {
        return [
            NavGroup::make(__('pennantplus::pennantplus.group'))->icon(Icons::svg('flag'))->sort(90),
        ];
    }

    public function navigation(): array
    {
        return [
            NavItem::make(__('pennantplus::pennantplus.features'))
                ->route('atrium.pennant.index')
                ->icon(Icons::svg('flag'))
                ->group(__('pennantplus::pennantplus.group'))
                ->sort(900),

            // The package's own audit log, while an audit log is installed.
            $this->historyNavItem('pennantplus')->group(__('pennantplus::pennantplus.group'))->sort(910),
        ];
    }

    public function routes(): void
    {
        Route::get('pennant', [FeatureFlagController::class, 'index'])->name('pennant.index');
        Route::get('pennant/scopes', [FeatureFlagController::class, 'scopes'])->name('pennant.scopes');
        Route::put('pennant/values', [FeatureFlagController::class, 'update'])->name('pennant.values.update');
        Route::delete('pennant/values', [FeatureFlagController::class, 'destroy'])->name('pennant.values.destroy');
        Route::delete('pennant/features', [FeatureFlagController::class, 'purge'])->name('pennant.features.purge');
    }
}
