<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium\Http\Requests\Concerns;

use RefactorCircus\PennantPlus\Atrium\FeatureFlagAccess;

/**
 * Feature flags have no model to hold a policy, so their requests defer to
 * the Pennant plugin's own authorization.
 */
trait AuthorizesFeatureFlags
{
    public function authorize(): bool
    {
        return FeatureFlagAccess::allows($this);
    }

    protected function redirectToFeatureFlags(): string
    {
        $previous = url()->previous();

        return str_starts_with($previous, route('atrium.pennant.index'))
            ? $previous
            : route('atrium.pennant.index');
    }
}
