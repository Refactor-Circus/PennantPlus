<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureResource;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\CheckFeatureMcpRequest;
use RefactorCircus\PennantPlus\Domains\Scope\Concerns\DescribesScope;

#[Description('Resolve a feature flag for one scope (global when none is given): the global value, the scope value, and whether the feature gate lets that scope through.')]
final class CheckFeatureTool extends Tool
{
    use DescribesScope;

    public function handle(CheckFeatureMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'feature' => $schema->string()->description('The feature name, e.g. a feature class name.')->required(),
            ...$this->scopeSchema($schema),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'feature' => $schema->string()->required(),
            'scope' => $schema->string()->description('The scope exactly as Pennant stores it.')->required(),
            'global' => $schema->union(FeatureResource::VALUE_TYPES)->description('The stored global value, null when none is stored.')->required(),
            'value' => $schema->union(FeatureResource::VALUE_TYPES)->description('The value the scope resolves to.')->required(),
            'active' => $schema->boolean()->description('Pennant treats any value other than false as active.')->required(),
            'gate' => $schema->boolean()->nullable()->description('Whether the feature gate lets the scope through; null for a scope that is neither global nor a user.')->required(),
        ];
    }
}
