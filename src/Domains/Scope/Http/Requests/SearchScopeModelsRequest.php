<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Scope\Actions\SearchScopeModelsAction;
use RefactorCircus\PennantPlus\Domains\Scope\Data\SearchScopeModelsData;
use RefactorCircus\PennantPlus\Http\Request;

final class SearchScopeModelsRequest extends Request
{
    public function rules(): array
    {
        return SearchScopeModelsData::rules();
    }

    public function persist(): JsonResponse
    {
        return new JsonResponse(['data' => app(SearchScopeModelsAction::class)->execute(
            SearchScopeModelsData::fromValidated($this->validated()),
        )]);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRouteParameter('type');
    }
}
