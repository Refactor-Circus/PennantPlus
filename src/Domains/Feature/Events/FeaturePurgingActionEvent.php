<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Data\PurgeFeatureData;

/**
 * Every stored value of a feature flag is about to be forgotten.
 */
final class FeaturePurgingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PurgeFeatureData $data,
    ) {}
}
