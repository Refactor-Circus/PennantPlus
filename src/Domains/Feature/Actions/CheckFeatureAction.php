<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use JayI\PennantPlus\Domains\Feature\Services\FeatureGate;
use JayI\PennantPlus\Domains\Scope\Support\ScopeRules;

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
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'feature' => ['required', 'string', 'max:255'],
            ...ScopeRules::optional(),
        ];
    }

    /**
     * @return array{feature: string, scope: string, global: mixed, value: mixed, active: bool, gate: bool|null}
     */
    public function execute(string $feature, string $scope): array
    {
        $scopeValue = $this->features->scopeValue($scope);

        $store = $this->features->store();

        $value = $store->for([$scopeValue])->value($feature);

        return [
            'feature' => $feature,
            'scope' => $scope,
            'global' => $store->for([null])->value($feature),
            'value' => $value,
            'active' => $value !== false,
            'gate' => $scopeValue === null || $scopeValue instanceof Authenticatable
                ? $this->gate->allows($feature, $scopeValue)
                : null,
        ];
    }
}
