<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Data\StoredFeatureValue;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueUpdatedActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueUpdatingActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\Domains\Scope\Support\ScopeRules;

/**
 * Store a feature's value for one serialized scope, through Pennant, so its
 * cache and events stay in step with the write.
 *
 * Setting the global value forgets every other scope's stored value when
 * `pennantplus.purge_scopes_on_global_update` is on, so no scope keeps a
 * value from before the change.
 */
final class UpdateFeatureValueAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'feature' => ['required', 'string', 'max:255'],
            ...ScopeRules::required(),
            'value' => ['present'],
        ];
    }

    public function execute(string $feature, string $scope, mixed $value): StoredFeatureValue
    {
        FeatureValueUpdatingActionEvent::dispatch($feature, $scope, $value);

        $result = $this->perform($feature, $scope, $value);

        FeatureValueUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(string $feature, string $scope, mixed $value): StoredFeatureValue
    {
        $global = $scope === Feature::serializeScope(null);

        DB::transaction(function () use ($feature, $scope, $value, $global): void {
            $this->features->store()
                ->for([$global ? null : $scope])
                ->activate($feature, $value);

            if ($global && $this->features->purgesScopesOnGlobalUpdate()) {
                $this->features->forgetScopedValues($feature);
            }
        });

        return new StoredFeatureValue($feature, $scope, $value, Carbon::now());
    }
}
