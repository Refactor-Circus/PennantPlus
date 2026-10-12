<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use RefactorCircus\PennantPlus\Domains\Feature\Data\PurgeFeatureData;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeaturePurgedActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeaturePurgingActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Forget every stored value of a feature, for every scope.
 */
final class PurgeFeatureAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    public function execute(PurgeFeatureData $data): void
    {
        FeaturePurgingActionEvent::dispatch($data);

        $this->perform($data->feature);

        FeaturePurgedActionEvent::dispatch($data->feature);
    }

    private function perform(string $feature): void
    {
        $this->features->transaction(function () use ($feature): void {
            $this->features->store()->purge($feature);
        });

        $this->features->forgetScopeTypes();
    }
}
