<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Atrium\Http\Requests\Concerns;

use JayI\Atrium\Plugins\PluginRegistry;
use JayI\PennantPlus\Atrium\PennantPlugin;

/**
 * Feature flags have no model to hold a policy, so their requests defer to
 * the Pennant plugin's own authorization.
 */
trait AuthorizesFeatureFlags
{
    public function authorize(): bool
    {
        $plugin = app(PluginRegistry::class)->get('pennant');

        return $plugin instanceof PennantPlugin && $plugin->authorize($this);
    }

    protected function redirectToFeatureFlags(): string
    {
        $previous = url()->previous();

        return str_starts_with($previous, route('atrium.pennant.index'))
            ? $previous
            : route('atrium.pennant.index');
    }
}
