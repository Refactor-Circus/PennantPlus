<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Data\CheckFeatureData;

/**
 * A feature flag is about to be resolved for a scope.
 */
final class FeatureCheckingActionEvent implements ActionStartingEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CheckFeatureData $data,
    ) {}
}
