<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;

/**
 * Every known feature flag has been listed.
 */
final class FeaturesListedActionEvent implements ActionFinishedEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;
}
