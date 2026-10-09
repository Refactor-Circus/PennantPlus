<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureGate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aborts with a 403 unless the FeatureGate lets the request's user through
 * every given feature.
 */
class EnsureFeatureActive
{
    public function __construct(private readonly FeatureGate $gate) {}

    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        foreach ($features as $feature) {
            abort_unless($this->gate->allows($feature, $request->user()), 403, __('pennantplus::pennantplus.feature_disabled'));
        }

        return $next($request);
    }
}
