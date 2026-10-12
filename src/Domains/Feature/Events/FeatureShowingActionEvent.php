<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Data\ShowFeatureData;

/**
 * A feature flag is about to be shown.
 */
final class FeatureShowingActionEvent implements ActionStartingEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ShowFeatureData $data,
    ) {}
}
