<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Data\DeleteFeatureValueData;

/**
 * A feature flag's stored value is about to be forgotten for a scope.
 */
final class FeatureValueDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public DeleteFeatureValueData $data,
        /** The scope as Pennant serializes it. */
        public string $scope,
    ) {}
}
