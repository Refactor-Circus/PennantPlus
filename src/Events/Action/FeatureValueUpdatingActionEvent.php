<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\PennantPlus\Contracts\ActionStartingEvent;

/**
 * A feature flag value is about to be stored for a scope.
 */
final class FeatureValueUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $feature,
        /** The scope as Pennant serializes it. */
        public string $scope,
        public mixed $value,
    ) {}
}
