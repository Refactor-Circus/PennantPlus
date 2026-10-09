<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Services;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;

/**
 * Decides whether a feature lets a user through an entry surface (a route, an
 * MCP tool, and so on).
 *
 * - A feature matching `pennantplus.gate.global_only` (by default every
 *   `*SupportFeature`) is checked against the global (null) scope only.
 * - Any other feature must be active globally AND for the user. Without a user
 *   only the global value counts.
 * - A user the bypass callback accepts is let through every feature.
 */
class FeatureGate
{
    /** @var (Closure(Authenticatable): bool)|null */
    private ?Closure $bypass = null;

    /**
     * Let users the callback accepts through every feature.
     *
     * @param  (Closure(Authenticatable): bool)|null  $callback
     */
    public function bypassUsing(?Closure $callback): static
    {
        $this->bypass = $callback;

        return $this;
    }

    public function allows(string $feature, ?Authenticatable $user = null): bool
    {
        if ($user !== null && $this->bypass !== null && ($this->bypass)($user) === true) {
            return true;
        }

        if (! Feature::for(null)->active($feature)) {
            return false;
        }

        if ($user === null || $this->isGlobalOnly($feature)) {
            return true;
        }

        return Feature::for($user)->active($feature);
    }

    public function isGlobalOnly(string $feature): bool
    {
        $patterns = config('pennantplus.gate.global_only', []);

        return Str::is(is_array($patterns) ? array_filter($patterns, is_string(...)) : [], $feature);
    }
}
