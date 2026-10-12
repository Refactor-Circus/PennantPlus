<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\PennantPlus\Domains\Scope\Support\ScopeRules;

/**
 * Input for CheckFeatureAction: the feature, and the scope to check it for -
 * serialized as Pennant stores it, or a type with its id. Without a scope the
 * global scope is checked.
 */
final readonly class CheckFeatureData extends Data
{
    public function __construct(
        public string $feature,
        public ?string $scope = null,
        public ?string $scopeType = null,
        public ?string $scopeId = null,
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
}
