<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Atrium\Features;

use JayI\PennantPlus\Domains\Feature\Support\OnLayeredFeature;

/**
 * Shows Atrium's theme switcher, beside the light/dark toggle. On until its
 * global value is set; give a user their own value to show or hide it for
 * them alone, since the feature gate checks it globally and per user.
 *
 * Atrium names this class in `atrium.themes.switcher_feature`. Point that
 * key at a subclass to change the default, or at a feature of your own.
 */
class ThemeSwitcherFeature extends OnLayeredFeature {}
