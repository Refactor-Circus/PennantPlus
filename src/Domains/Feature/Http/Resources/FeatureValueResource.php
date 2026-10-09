<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RefactorCircus\PennantPlus\Domains\Feature\Data\StoredFeatureValue;

/**
 * @property StoredFeatureValue $resource
 */
final class FeatureValueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'feature' => $this->resource->feature,
            'scope' => $this->resource->scope,
            'scope_type' => $this->resource->scopeType(),
            'scope_id' => $this->resource->scopeId(),
            'scope_label' => $this->resource->scopeLabel(),
            'title' => $this->resource->title,
            'global' => $this->resource->isGlobal(),
            'value' => $this->resource->value,
            'active' => $this->resource->isActive(),
            'updated_at' => $this->resource->updatedAt?->toIso8601String(),
        ];
    }
}
