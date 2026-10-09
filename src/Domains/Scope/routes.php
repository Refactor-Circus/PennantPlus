<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\PennantPlus\Domains\Scope\Http\Controllers\ScopeController;

// Scope types are class names: allow backslashes, never slashes.
Route::get('scopes', [ScopeController::class, 'index'])->name('scopes.index');
Route::get('scopes/{type}/models', [ScopeController::class, 'models'])->where('type', '[^/]+')->name('scopes.models');
