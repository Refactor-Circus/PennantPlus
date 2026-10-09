<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Controllers\FeatureController;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Controllers\FeatureValueController;

// Feature names are class names: allow backslashes, never slashes.
Route::get('features', [FeatureController::class, 'index'])->name('features.index');
Route::get('features/{feature}', [FeatureController::class, 'show'])->where('feature', '[^/]+')->name('features.show');
Route::get('features/{feature}/check', [FeatureController::class, 'check'])->where('feature', '[^/]+')->name('features.check');
Route::delete('features/{feature}', [FeatureController::class, 'destroy'])->where('feature', '[^/]+')->name('features.destroy');

Route::get('values', [FeatureValueController::class, 'index'])->name('values.index');
Route::put('values', [FeatureValueController::class, 'update'])->name('values.update');
Route::delete('values', [FeatureValueController::class, 'destroy'])->name('values.destroy');
