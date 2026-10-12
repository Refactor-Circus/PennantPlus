<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
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

    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'feature' => $schema->string()->required(),
            'scope' => $schema->string()->description('The scope exactly as Pennant stores it.')->required(),
            'scope_type' => $schema->string()->nullable()->description('The model type of a model scope.')->required(),
            'scope_id' => $schema->string()->nullable()->description('The model key of a model scope.')->required(),
            'scope_label' => $schema->string()->required(),
            'title' => $schema->string()->nullable()->description('How the scope model is shown, for a configured scope model.')->required(),
            'global' => $schema->boolean()->required(),
            'value' => $schema->union(FeatureResource::VALUE_TYPES)->required(),
            'active' => $schema->boolean()->description('Pennant treats any value other than false as active.')->required(),
            'updated_at' => $schema->string()->nullable()->required(),
        ];
    }
}
