<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\PennantPlus\Domains\Feature\Data\CheckFeatureData;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureCheckedActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureCheckingActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureGate;

/**
 * Resolve a feature for one scope: the global value, the scope's value, and
 * whether the FeatureGate lets that scope through.
 */
final class CheckFeatureAction
{
    public function __construct(
        private readonly FeatureFlagManager $features,
        private readonly FeatureGate $gate,
    ) {}

    /**
     * @return array{feature: string, scope: string, global: mixed, value: mixed, active: bool, gate: bool|null}
     */
    public function execute(CheckFeatureData $data): array
    {
        FeatureCheckingActionEvent::dispatch($data);

        $result = $this->perform($data);

        FeatureCheckedActionEvent::dispatch($data);

        return $result;
    }

    /**
     * @return array{feature: string, scope: string, global: mixed, value: mixed, active: bool, gate: bool|null}
     */
    private function perform(CheckFeatureData $data): array
    {
        $scope = $this->features->scopeFromInput($data->toArray());

        $scopeValue = $this->features->scopeValue($scope);

        $store = $this->features->store();

        $value = $store->for([$scopeValue])->value($data->feature);

        return [
            'feature' => $data->feature,
            'scope' => $scope,
            'global' => $store->for([null])->value($data->feature),
            'value' => $value,
            'active' => $value !== false,
            'gate' => $scopeValue === null || $scopeValue instanceof Authenticatable
                ? $this->gate->allows($data->feature, $scopeValue)
                : null,
        ];
    }
}
