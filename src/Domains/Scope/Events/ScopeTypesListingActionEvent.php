<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;

/**
 * The scope types a value can target are about to be listed.
 */
final class ScopeTypesListingActionEvent implements ActionStartingEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;
}
