<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\PennantPlus\Contracts\ActionFinishedEvent;
use JayI\PennantPlus\Domains\Feature\Data\StoredFeatureValue;

/**
 * A feature flag value was stored for a scope.
 */
final class FeatureValueUpdatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public StoredFeatureValue $value,
    ) {}
}
