<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\PennantPlus\Domains\Scope\Actions\SearchScopeModelsAction;
use RefactorCircus\PennantPlus\Mcp\Request;

final class SearchScopeModelsMcpRequest extends Request
{
    protected function rules(): array
    {
        return SearchScopeModelsAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->structuredCollection(app(SearchScopeModelsAction::class)->execute(
            (string) $validated['type'],
            isset($validated['q']) ? (string) $validated['q'] : null,
        ));
    }
}
