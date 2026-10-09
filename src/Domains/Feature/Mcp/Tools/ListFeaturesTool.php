<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use RefactorCircus\Foundation\Mcp\Tool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\ListFeaturesMcpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List every feature flag that is defined, discoverable, or has a stored value, with its stored global value and how many other scopes hold a value.')]
final class ListFeaturesTool extends Tool
{
    public function handle(ListFeaturesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
