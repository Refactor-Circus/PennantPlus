<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;
use RefactorCircus\PennantPlus\Domains\Scope\Data\SearchScopeModelsData;

/**
 * Models of a scope type are about to be searched.
 */
final class ScopeModelsSearchingActionEvent implements ActionStartingEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SearchScopeModelsData $data,
    ) {}
}
