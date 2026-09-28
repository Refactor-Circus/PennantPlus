<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\PennantPlus\Http\Controllers\FeatureController;
use JayI\PennantPlus\Http\Controllers\FeatureValueController;
use JayI\PennantPlus\Http\Controllers\ScopeController;

/** @var string $prefix */
$prefix = config('pennantplus.routes.prefix');

/** @var array<int, string> $middleware */
$middleware = config('pennantplus.routes.middleware');

// Feature names and scope types are class names: allow backslashes, never slashes.
Route::prefix($prefix)->middleware($middleware)->name('pennantplus.')->group(function (): void {
    Route::get('features', [FeatureController::class, 'index'])->name('features.index');
    Route::get('features/{feature}', [FeatureController::class, 'show'])->where('feature', '[^/]+')->name('features.show');
    Route::get('features/{feature}/check', [FeatureController::class, 'check'])->where('feature', '[^/]+')->name('features.check');
    Route::delete('features/{feature}', [FeatureController::class, 'destroy'])->where('feature', '[^/]+')->name('features.destroy');

    Route::get('values', [FeatureValueController::class, 'index'])->name('values.index');
    Route::put('values', [FeatureValueController::class, 'update'])->name('values.update');
    Route::delete('values', [FeatureValueController::class, 'destroy'])->name('values.destroy');

    Route::get('scopes', [ScopeController::class, 'index'])->name('scopes.index');
    Route::get('scopes/{type}/models', [ScopeController::class, 'models'])->where('type', '[^/]+')->name('scopes.models');
});
