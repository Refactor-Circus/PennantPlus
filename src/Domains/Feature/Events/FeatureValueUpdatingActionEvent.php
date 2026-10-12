<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Data\UpdateFeatureValueData;

/**
 * A feature flag value is about to be stored for a scope.
 */
final class FeatureValueUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public UpdateFeatureValueData $data,
        /** The scope as Pennant serializes it. */
        public string $scope,
    ) {}
}
