<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Requests;

use JayI\PennantPlus\Actions\ListScopeTypesAction;
use JayI\PennantPlus\Mcp\Request;
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
