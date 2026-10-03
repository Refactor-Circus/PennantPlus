<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
}
