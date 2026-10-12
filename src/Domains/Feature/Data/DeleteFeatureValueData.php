<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\PennantPlus\Domains\Scope\Support\ScopeRules;

/**
 * Input for DeleteFeatureValueAction: the feature, and the scope whose value
 * to forget - serialized as Pennant stores it, or a type with its id.
 */
final readonly class DeleteFeatureValueData extends Data
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
            ...ScopeRules::required(),
        ];
    }
}
