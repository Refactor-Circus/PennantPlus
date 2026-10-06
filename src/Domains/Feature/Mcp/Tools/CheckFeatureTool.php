<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\PennantPlus\Domains\Feature\Mcp\Requests\CheckFeatureMcpRequest;
use JayI\PennantPlus\Domains\Scope\Concerns\DescribesScope;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

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
}
