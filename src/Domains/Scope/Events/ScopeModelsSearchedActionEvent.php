<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;
use RefactorCircus\PennantPlus\Domains\Scope\Data\SearchScopeModelsData;

/**
 * Models of a scope type have been searched.
 */
final class ScopeModelsSearchedActionEvent implements ActionFinishedEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SearchScopeModelsData $data,
    ) {}
}
