<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Actions\SearchScopeModelsAction;
use JayI\PennantPlus\Http\Request;

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
