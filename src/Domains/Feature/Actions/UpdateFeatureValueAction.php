<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Data\StoredFeatureValue;
use RefactorCircus\PennantPlus\Domains\Feature\Data\UpdateFeatureValueData;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueUpdatedActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueUpdatingActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Store a feature's value for one scope, through Pennant, so its
 * cache and events stay in step with the write.
 *
 * Setting the global value forgets every other scope's stored value when
 * `pennantplus.purge_scopes_on_global_update` is on, so no scope keeps a
 * value from before the change. Those values are forgotten once the global
 * value is committed, in chunks, so a feature with many overrides holds no
 * long transaction; should forgetting fail part way, the global value stands
 * and the action can simply be run again.
 */
final class UpdateFeatureValueAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    public function execute(UpdateFeatureValueData $data): StoredFeatureValue
    {
        $scope = $this->features->scopeFromInput($data->toArray());

        FeatureValueUpdatingActionEvent::dispatch($data, $scope);

        $result = $this->perform($data->feature, $scope, $data->value);

        FeatureValueUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(string $feature, string $scope, mixed $value): StoredFeatureValue
    {
        $global = $scope === Feature::serializeScope(null);

        $this->features->transaction(function () use ($feature, $scope, $value, $global): void {
            $this->features->store()
                ->for([$global ? null : $scope])
                ->activate($feature, $value);
        });

        if ($global && $this->features->purgesScopesOnGlobalUpdate()) {
            $this->features->forgetScopedValues($feature);
        }

        $this->features->forgetScopeTypes();

        return new StoredFeatureValue($feature, $scope, $value, Carbon::now());
    }
}
