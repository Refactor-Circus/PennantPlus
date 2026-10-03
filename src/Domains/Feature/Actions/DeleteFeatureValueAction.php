<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Actions;

use Illuminate\Support\Facades\DB;
use JayI\PennantPlus\Domains\Feature\Events\FeatureValueDeletedActionEvent;
use JayI\PennantPlus\Domains\Feature\Events\FeatureValueDeletingActionEvent;
use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use JayI\PennantPlus\Domains\Scope\Support\ScopeRules;
use Laravel\Pennant\Feature;

/**
 * Forget a feature's stored value for one serialized scope, so Pennant
 * resolves it afresh the next time it is checked.
 */
final class DeleteFeatureValueAction
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
        ];
    }

    public function execute(string $feature, string $scope): void
    {
        FeatureValueDeletingActionEvent::dispatch($feature, $scope);

        $this->perform($feature, $scope);

        FeatureValueDeletedActionEvent::dispatch($feature, $scope);
    }

    private function perform(string $feature, string $scope): void
    {
        DB::transaction(function () use ($feature, $scope): void {
            $this->features->store()
                ->for([$scope === Feature::serializeScope(null) ? null : $scope])
                ->forget($feature);
        });
    }
}
