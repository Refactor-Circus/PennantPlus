<?php

namespace Workbench\App\Features;

use JayI\PennantPlus\Domains\Feature\Support\OnLayeredFeature;

/**
 * Demo: a global-only kill switch, matched by `pennantplus.gate.global_only`
 * (`*SupportFeature`), so the feature gate never checks it per user.
 */
class BillingSupportFeature extends OnLayeredFeature {}
