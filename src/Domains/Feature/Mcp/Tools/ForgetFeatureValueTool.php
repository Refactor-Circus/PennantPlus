<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Foundation\Mcp\Tool;
use JayI\PennantPlus\Domains\Feature\Mcp\Requests\ForgetFeatureValueMcpRequest;
use JayI\PennantPlus\Domains\Scope\Concerns\DescribesScope;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Forget the stored value of a feature flag for one scope, so that scope follows the global value again.')]
final class ForgetFeatureValueTool extends Tool
{
    use DescribesScope;

    public function handle(ForgetFeatureValueMcpRequest $request): Response|ResponseFactory
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
