<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\PennantPlus\Mcp\Requests\PurgeFeatureMcpRequest;
use JayI\PennantPlus\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Forget every stored value of a feature flag, for every scope, so it resolves afresh.')]
final class PurgeFeatureTool extends Tool
{
    public function handle(PurgeFeatureMcpRequest $request): Response|ResponseFactory
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
