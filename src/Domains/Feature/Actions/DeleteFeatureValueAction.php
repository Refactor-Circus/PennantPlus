<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Data\DeleteFeatureValueData;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueDeletedActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueDeletingActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Forget a feature's stored value for one scope, so Pennant resolves it
 * afresh the next time it is checked.
 */
final class DeleteFeatureValueAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    public function execute(DeleteFeatureValueData $data): void
    {
        $scope = $this->features->scopeFromInput($data->toArray());

        FeatureValueDeletingActionEvent::dispatch($data, $scope);

        $this->perform($data->feature, $scope);

        FeatureValueDeletedActionEvent::dispatch($data->feature, $scope);
    }

    private function perform(string $feature, string $scope): void
    {
        $this->features->transaction(function () use ($feature, $scope): void {
            $this->features->store()
                ->for([$scope === Feature::serializeScope(null) ? null : $scope])
                ->forget($feature);
        });

        $this->features->forgetScopeTypes();
    }
}
