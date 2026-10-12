<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;

/**
 * The scope types a value can target have been listed.
 */
final class ScopeTypesListedActionEvent implements ActionFinishedEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;
}
