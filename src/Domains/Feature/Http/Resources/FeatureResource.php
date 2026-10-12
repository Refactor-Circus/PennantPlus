<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * A feature summary: its name, whether Pennant knows it, its stored global
 * value, and how many other scopes hold a value. `value`, the resolved global
 * value, is present when the feature was shown on its own.
 *
 * @property array{name: string, defined: bool, global: mixed, global_stored: bool, overrides: int, value?: mixed} $resource
 */
final class FeatureResource extends JsonResource
{
    /**
     * Feature values are any JSON value: true, false, or something richer.
     */
    public const array VALUE_TYPES = ['boolean', 'integer', 'number', 'string', 'array', 'object', 'null'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'defined' => $this->resource['defined'],
            'global' => $this->resource['global'],
            'global_stored' => $this->resource['global_stored'],
            'overrides' => $this->resource['overrides'],
            'value' => $this->when(array_key_exists('value', $this->resource), fn (): mixed => $this->resource['value'] ?? null),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'defined' => $schema->boolean()->description('Whether Pennant knows the feature: defined, or discovered from a feature class.')->required(),
            'global' => $schema->union(self::VALUE_TYPES)->description('The stored global value, null when none is stored.')->required(),
            'global_stored' => $schema->boolean()->required(),
            'overrides' => $schema->integer()->description('How many other scopes hold a stored value.')->required(),
            'value' => $schema->union(self::VALUE_TYPES)->description('The resolved global value; present when one feature is shown.'),
        ];
    }
}
