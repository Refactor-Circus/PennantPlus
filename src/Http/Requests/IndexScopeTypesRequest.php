<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Actions\ListScopeTypesAction;
use JayI\PennantPlus\Http\Request;

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
