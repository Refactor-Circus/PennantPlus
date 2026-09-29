<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\PennantPlus\Mcp\Requests\ShowFeatureMcpRequest;
use JayI\PennantPlus\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Show one feature flag: its stored global value, override count, and resolved global value.')]
final class ShowFeatureTool extends Tool
{
    public function handle(ShowFeatureMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'feature' => $schema->string()->description('The feature name, e.g. a feature class name.')->required(),
        ];
    }
}
