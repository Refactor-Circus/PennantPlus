<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Actions;

use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\Domains\Scope\Events\ScopeTypesListedActionEvent;
use RefactorCircus\PennantPlus\Domains\Scope\Events\ScopeTypesListingActionEvent;

/**
 * The scope types a value can target: `global`, the configured models, any
 * other model type that has stored values, and `other` string scopes.
 */
final class ListScopeTypesAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array<int, array{type: string, label: string, searchable: bool}>
     */
    public function execute(): array
    {
        ScopeTypesListingActionEvent::dispatch();

        $types = [[
            'type' => FeatureFlagManager::GLOBAL,
            'label' => __('pennantplus::pennantplus.global'),
            'searchable' => false,
        ]];

        foreach ($this->features->scopeFilterOptions() as $type => $label) {
            $types[] = [
                'type' => $type,
                'label' => $label,
                'searchable' => $this->features->scopeModel($type) !== null,
            ];
        }

        $types[] = [
            'type' => FeatureFlagManager::OTHER,
            'label' => __('pennantplus::pennantplus.other'),
            'searchable' => false,
        ];

        ScopeTypesListedActionEvent::dispatch();

        return $types;
    }
}
