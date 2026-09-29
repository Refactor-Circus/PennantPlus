<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\PennantPlus\Mcp\Requests\ListScopeTypesMcpRequest;
use JayI\PennantPlus\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List the scope types a feature flag value can target: global, configured models, other model types with stored values, and plain string scopes.')]
final class ListScopeTypesTool extends Tool
{
    public function handle(ListScopeTypesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
