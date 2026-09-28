<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Requests;

use JayI\PennantPlus\Actions\SearchScopeModelsAction;
use JayI\PennantPlus\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

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
