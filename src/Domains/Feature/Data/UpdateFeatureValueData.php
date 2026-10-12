<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\PennantPlus\Domains\Scope\Support\ScopeRules;

/**
 * Input for UpdateFeatureValueAction: the feature, the value to store - true,
 * false, or any richer JSON value - and the scope to store it for,
 * serialized as Pennant stores it, or a type with its id.
 */
final readonly class UpdateFeatureValueData extends Data
{
    public function __construct(
        public string $feature,
        public mixed $value,
        public ?string $scope = null,
        public ?string $scopeType = null,
        public ?string $scopeId = null,
    ) {}

    /**
     * Pass `encoded` when the value arrives JSON-encoded, as from an MCP tool
     * or a form; decode it before building the data.
     *
     * @return array<string, mixed>
     */
    public static function rules(bool $encoded = false): array
    {
        return [
            'feature' => ['required', 'string', 'max:255'],
            ...ScopeRules::required(),
            'value' => $encoded ? ['required', 'json'] : ['present'],
        ];
    }

    /**
     * Build the data from validated input whose value is JSON-encoded.
     *
     * @param  array<array-key, mixed>  $validated
     */
    public static function fromEncoded(array $validated): self
    {
        return self::fromValidated([
            ...$validated,
            'value' => json_decode((string) ($validated['value'] ?? ''), true, flags: JSON_THROW_ON_ERROR),
        ]);
    }
}
