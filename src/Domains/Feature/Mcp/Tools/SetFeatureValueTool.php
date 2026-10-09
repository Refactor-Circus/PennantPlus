<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Foundation\Mcp\Tool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\SetFeatureValueMcpRequest;
use RefactorCircus\PennantPlus\Domains\Scope\Concerns\DescribesScope;

#[Description('Store a feature flag value for a scope. Setting the global value forgets every other scope value of that feature, so they follow the new global value.')]
final class SetFeatureValueTool extends Tool
{
    use DescribesScope;

    public function handle(SetFeatureValueMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'feature' => $schema->string()->description('The feature name, e.g. a feature class name.')->required(),
            ...$this->scopeSchema($schema),
            'value' => $schema->string()->description('The value, JSON-encoded: "true", "false", or any richer JSON value.')->required(),
        ];
    }
}
