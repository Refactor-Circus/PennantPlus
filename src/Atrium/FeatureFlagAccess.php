<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium;

use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Plugins\Services\PluginRegistry;

/**
 * Whether a request may manage feature flags from Atrium, asked the way the
 * Feature flags screen asks it: through the Pennant plugin's authorize(),
 * which checks `pennantplus.ability` when one is configured.
 *
 * The controller and form requests refuse with it and the views hide
 * controls with it (as `@pennantplusManages`), so a control is shown exactly
 * when the action behind it is allowed.
 */
final class FeatureFlagAccess
{
    public static function allows(?Request $request = null): bool
    {
        $plugin = app(PluginRegistry::class)->get('pennant');

        return $plugin instanceof PennantPlugin && $plugin->authorize($request ?? request());
    }
}
