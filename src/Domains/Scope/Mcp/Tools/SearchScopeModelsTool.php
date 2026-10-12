<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\PennantPlus\Domains\Scope\Mcp\Requests\SearchScopeModelsMcpRequest;

#[Description('Find models of a searchable scope type by key or search columns, with the serialized scope to use in other tools.')]
final class SearchScopeModelsTool extends Tool
{
    public function handle(SearchScopeModelsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('A searchable scope type from list-scope-types.')->required(),
            'q' => $schema->string()->description('The search term: a model key or part of a searched column.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()->items($schema->object([
                'id' => $schema->string()->description('The model key.')->required(),
                'title' => $schema->string()->required(),
                'scope' => $schema->string()->description('The scope exactly as Pennant stores it, to pass as scope to other tools.')->required(),
            ]))->required(),
        ];
    }
}
