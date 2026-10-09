<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Scope\Actions\SearchScopeModelsAction;
use RefactorCircus\PennantPlus\Http\Request;

final class SearchScopeModelsRequest extends Request
{
    public function rules(): array
    {
        return SearchScopeModelsAction::rules();
    }

    public function persist(): JsonResponse
    {
        return new JsonResponse(['data' => app(SearchScopeModelsAction::class)->execute(
            $this->string('type')->toString(),
            $this->string('q')->toString(),
        )]);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRouteParameter('type');
    }
}
