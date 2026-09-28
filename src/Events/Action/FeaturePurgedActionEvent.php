<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\PennantPlus\Contracts\ActionFinishedEvent;

/**
 * Every stored value of a feature flag was forgotten.
 */
final class FeaturePurgedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $feature,
    ) {}
}
