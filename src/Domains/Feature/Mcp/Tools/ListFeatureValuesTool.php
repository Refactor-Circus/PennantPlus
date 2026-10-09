<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Foundation\Mcp\Tool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\ListFeatureValuesMcpRequest;

#[Description('List stored feature flag values, newest first. Filter by feature and by scope: "global", "other", or a model type (optionally one model key). Paginated.')]
final class ListFeatureValuesTool extends Tool
{
    public function handle(ListFeatureValuesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'feature' => $schema->string()->description('Only values of this feature.'),
            'scope' => $schema->string()->description('Scope filter: "global", "other", or a model type.'),
            'scope_id' => $schema->string()->description('With a model type, only this model key; with "other", only this string scope.'),
            'page' => $schema->integer()->description('Page number, starting at 1.')->min(1),
        ];
    }
}
