<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Mcp\Requests;

use RefactorCircus\PennantPlus\Domains\Scope\Actions\ListScopeTypesAction;
use RefactorCircus\PennantPlus\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class ListScopeTypesMcpRequest extends Request
{
    protected function rules(): array
    {
        return ListScopeTypesAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->structuredCollection(app(ListScopeTypesAction::class)->execute());
    }
}
