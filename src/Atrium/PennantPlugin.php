<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Atrium;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use JayI\Atrium\Navigation\NavItem;
use JayI\Atrium\Plugins\Plugin;
use JayI\PennantPlus\Atrium\Http\Controllers\FeatureFlagController;
use JayI\PennantPlus\FeatureFlagManager;

/**
 * Manages the feature flag values Pennant has stored.
 *
 * Atrium discovers it from composer.json when jayi/atrium is installed. Hide
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

    public function navigation(): array
    {
        return [
            NavItem::make(__('pennantplus::pennantplus.features'))
                ->route('atrium.pennant.index')
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" /></svg>')
                ->group(__('pennantplus::pennantplus.group'))
                ->sort(900),
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
