<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\PennantPlus\Mcp\Requests\ListFeaturesMcpRequest;
use JayI\PennantPlus\Mcp\Tool;
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
