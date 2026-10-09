<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\PennantPlus\Domains\Scope\Mcp\Requests\ListScopeTypesMcpRequest;

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
