<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\ForgetFeatureValueMcpRequest;
use RefactorCircus\PennantPlus\Domains\Scope\Concerns\DescribesScope;

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
