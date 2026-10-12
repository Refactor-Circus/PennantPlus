<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Data\ListFeatureValuesData;

/**
 * Stored feature flag values have been listed.
 */
final class FeatureValuesListedActionEvent implements ActionFinishedEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListFeatureValuesData $filters,
    ) {}
}
