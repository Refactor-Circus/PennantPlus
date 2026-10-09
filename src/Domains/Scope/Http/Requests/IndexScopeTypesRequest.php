<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Scope\Actions\ListScopeTypesAction;
use RefactorCircus\PennantPlus\Http\Request;

final class IndexScopeTypesRequest extends Request
{
    public function rules(): array
    {
        return ListScopeTypesAction::rules();
    }

    public function persist(): JsonResponse
    {
        return new JsonResponse(['data' => app(ListScopeTypesAction::class)->execute()]);
    }
}
